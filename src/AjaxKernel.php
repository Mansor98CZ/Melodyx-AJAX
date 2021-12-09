<?php


namespace Melodyx\Ajax;

use Melodyx\Ajax\Attribute\PrerenderMethod;
use Melodyx\Ajax\Exception\InvalidControllerParent;
use Melodyx\Ajax\Exception\PrerenderAttributeNotDefined;
use Melodyx\Ajax\Piece\PieceEvent;
use Melodyx\Ajax\Twig\PieceIdentifierCollector;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Exception\RequestExceptionInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolverInterface;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ControllerDoesNotReturnResponseException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\TerminableInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use function Symfony\Component\DependencyInjection\Loader\Configurator\ref;

// Help opcache.preload discover always-needed symbols
class_exists(ControllerArgumentsEvent::class);
class_exists(ControllerEvent::class);
class_exists(ExceptionEvent::class);
class_exists(FinishRequestEvent::class);
class_exists(RequestEvent::class);
class_exists(ResponseEvent::class);
class_exists(TerminateEvent::class);
class_exists(ViewEvent::class);
class_exists(KernelEvents::class);

class AjaxKernel implements HttpKernelInterface, TerminableInterface
{
    public const PIECE_RENDER_EVENT = 'piece.render';
    protected $dispatcher;
    protected $resolver;
    protected $requestStack;
    private $argumentResolver;

    public function __construct(EventDispatcherInterface $dispatcher, ControllerResolverInterface $resolver, RequestStack $requestStack = null, ArgumentResolverInterface $argumentResolver = null, private ContainerInterface $container)
    {
        $this->dispatcher = $dispatcher;
        $this->resolver = $resolver;
        $this->requestStack = $requestStack ?? new RequestStack();
        $this->argumentResolver = $argumentResolver;

        if (null === $this->argumentResolver) {
            $this->argumentResolver = new ArgumentResolver();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function handle(Request $request, int $type = HttpKernelInterface::MAIN_REQUEST, bool $catch = true)
    {
        $request->headers->set('X-Php-Ob-Level', (string)ob_get_level());

        try {
            return $this->handleRaw($request, $type);
        } catch (\Exception $e) {
            if ($e instanceof RequestExceptionInterface) {
                $e = new BadRequestHttpException($e->getMessage(), $e);
            }
            if (false === $catch) {
                $this->finishRequest($request, $type);

                throw $e;
            }

            return $this->handleThrowable($e, $request, $type);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function terminate(Request $request, Response $response)
    {
        $this->dispatcher->dispatch(new TerminateEvent($this, $request, $response), KernelEvents::TERMINATE);
    }

    /**
     * @internal
     */
    public function terminateWithException(\Throwable $exception, Request $request = null)
    {
        if (!$request = $request ?: $this->requestStack->getMainRequest()) {
            throw $exception;
        }

        $response = $this->handleThrowable($exception, $request, self::MAIN_REQUEST);

        $response->sendHeaders();
        $response->sendContent();

        $this->terminate($request, $response);
    }

    /**
     * Handles a request to convert it to a response.
     *
     * Exceptions are not caught.
     *
     * @throws \LogicException       If one of the listener does not behave as expected
     * @throws NotFoundHttpException When controller cannot be found
     */
    private function handleRaw(Request $request, int $type = self::MAIN_REQUEST): Response
    {
        $request->attributes->set('no_rewrite', false);
        $this->requestStack->push($request);

        // request
        $event = $this->createEvent($request, $type);
        $controller = $event->getController();
        $arguments = $event->getArguments();
        /** @var AbstractAjaxController $controllerInstance */
        $controllerInstance = $controller[0];
        $handleMethod = $controller[1];
        if (!$controllerInstance instanceof AbstractAjaxController) {
            throw new InvalidControllerParent($controllerInstance::class);
        }
        $controllerInstance->setRequest($request);
//            try {
//                $attribute = $this->resolveAttributes($controllerInstance, $handleMethod);
//            } catch (PrerenderAttributeNotDefined $e) {
//                dump($e);
//            }
//            if (isset($attribute) && $attribute instanceof PrerenderMethod) {
//                $value = $controllerInstance::class . '::' . $attribute->getMethodName();
//                $request->attributes->set('_controller', $value);
//                $request->attributes->set('no_rewrite', true);
//                $event = $this->createEvent($request, $type);
//                $response = $event->getController()(...$event->getArguments());
////                $event = new ViewEvent($this, $request, $type, $response);
//                $event = new PieceEvent($this, $request, $response, $type);
//                $this->dispatcher->dispatch($event, self::PIECE_RENDER_EVENT);
//                // TODO někde tady by se mělo asi udělat nějak něco aby se vyrenderovala šablona
//            }
        // call controller
        $controller(...$arguments);

        // Call shutdown and invoke Response
        $response = $controllerInstance->onShutdown();

        return $this->filterResponse($response, $request, $type);
    }

    /**
     * Filters a response object.
     *
     * @throws \RuntimeException if the passed object is not a Response instance
     */
    private function filterResponse(Response $response, Request $request, int $type): Response
    {
        $event = new ResponseEvent($this, $request, $type, $response);

        $this->dispatcher->dispatch($event, KernelEvents::RESPONSE);

        $this->finishRequest($request, $type);

        return $event->getResponse();
    }

    /**
     * Publishes the finish request event, then pop the request from the stack.
     *
     * Note that the order of the operations is important here, otherwise
     * operations such as {@link RequestStack::getParentRequest()} can lead to
     * weird results.
     */
    private function finishRequest(Request $request, int $type)
    {
        $this->dispatcher->dispatch(new FinishRequestEvent($this, $request, $type), KernelEvents::FINISH_REQUEST);
        $this->requestStack->pop();
    }

    /**
     * Handles a throwable by trying to convert it to a Response.
     *
     * @throws \Exception
     */
    private function handleThrowable(\Throwable $e, Request $request, int $type): Response
    {
        $response = new Response();
        $response->setContent($e->getMessage());
        return $response;
        $event = new ExceptionEvent($this, $request, $type, $e);
        $this->dispatcher->dispatch($event, KernelEvents::EXCEPTION);

        // a listener might have replaced the exception
        $e = $event->getThrowable();

        if (!$event->hasResponse()) {
            $this->finishRequest($request, $type);

            throw $e;
        }

        $response = $event->getResponse();

        // the developer asked for a specific status code
        if (!$event->isAllowingCustomResponseCode() && !$response->isClientError() && !$response->isServerError() && !$response->isRedirect()) {
            // ensure that we actually have an error response
            if ($e instanceof HttpExceptionInterface) {
                // keep the HTTP status code and headers
                $response->setStatusCode($e->getStatusCode());
                $response->headers->add($e->getHeaders());
            } else {
                $response->setStatusCode(500);
            }
        }

        try {
            return $this->filterResponse($response, $request, $type);
        } catch (\Exception $e) {
            return $response;
        }
    }

    /**
     * Returns a human-readable string for the specified variable.
     */
    private function varToString($var): string
    {
        if (\is_object($var)) {
            return sprintf('an object of type %s', \get_class($var));
        }

        if (\is_array($var)) {
            $a = [];
            foreach ($var as $k => $v) {
                $a[] = sprintf('%s => ...', $k);
            }

            return sprintf('an array ([%s])', mb_substr(implode(', ', $a), 0, 255));
        }

        if (\is_resource($var)) {
            return sprintf('a resource (%s)', get_resource_type($var));
        }

        if (null === $var) {
            return 'null';
        }

        if (false === $var) {
            return 'a boolean value (false)';
        }

        if (true === $var) {
            return 'a boolean value (true)';
        }

        if (\is_string($var)) {
            return sprintf('a string ("%s%s")', mb_substr($var, 0, 255), mb_strlen($var) > 255 ? '...' : '');
        }

        if (is_numeric($var)) {
            return sprintf('a number (%s)', (string)$var);
        }

        return (string)$var;
    }

    /**
     * @param AbstractAjaxController $controllerInstance
     * @param $handleMethod
     * @throws \ReflectionException
     */
    private function resolveAttributes(AbstractAjaxController $controllerInstance, $handleMethod): PrerenderMethod
    {
        $reflection = new \ReflectionClass($controllerInstance);
        $reflectionMethod = $reflection->getMethod($handleMethod);
        $attributes = $reflectionMethod->getAttributes(PrerenderMethod::class);
        if (empty($attributes)) {
            throw new PrerenderAttributeNotDefined();
        }
        /** @var PrerenderMethod $prerenderMethodName */
        $prerenderMethodName = $attributes[0]->newInstance();
        return $prerenderMethodName;
    }


    private function createEvent(Request $request, int $type): ControllerArgumentsEvent
    {
        $event = new RequestEvent($this, $request, $type);
        $this->dispatcher->dispatch($event, KernelEvents::REQUEST);

//        if ($event->hasResponse()) {
//            return $this->filterResponse($event->getResponse(), $request, $type);
//        }

        // load controller
        if (false === $controller = $this->resolver->getController($request)) {
            throw new NotFoundHttpException(sprintf('Unable to find the controller for path "%s". The route is wrongly configured.', $request->getPathInfo()));
        }
        $event = new ControllerEvent($this, $controller, $request, $type);
        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER);
        $controller = $event->getController();

        // controller arguments
        $arguments = $this->argumentResolver->getArguments($request, $controller);

        $event = new ControllerArgumentsEvent($this, $controller, $arguments, $request, $type);
        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER_ARGUMENTS);
        /** @var AbstractAjaxController $controller */
        [$controller, $method] = $event->getController();
        $controller->setContainer($this->container);
        return $event;
    }

}