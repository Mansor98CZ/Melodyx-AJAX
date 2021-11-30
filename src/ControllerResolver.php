<?php


namespace Melodyx\Ajax;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolverInterface;

class ControllerResolver implements ControllerResolverInterface
{

    private Request $request;

    public function getController(Request $request)
    {
        $this->request = $request;
        if (!$controller = $request->attributes->get('_controller')) {
            return false;
        }
        if ($request->attributes->get('no_rewrite') === false) {

            $handle = 'handle' . ucfirst($request->get('handle'));
            $handleTransform = explode('::', $controller);

            $handleTransform[1] = $handle;
            $controller = implode('::', $handleTransform);
        }

        if (\is_array($controller)) {
            if (isset($controller[0]) && \is_string($controller[0]) && isset($controller[1])) {
                try {
                    $controller[0] = $this->instantiateController($controller[0]);
                } catch (\Error | \LogicException $e) {
                    try {
                        // We cannot just check is_callable but have to use reflection because a non-static method
                        // can still be called statically in PHP but we don't want that. This is deprecated in PHP 7, so we
                        // could simplify this with PHP 8.
                        if ((new \ReflectionMethod($controller[0], $controller[1]))->isStatic()) {
                            return $controller;
                        }
                    } catch (\ReflectionException $reflectionException) {
                        throw $e;
                    }

                    throw $e;
                }
            }

            if (!\is_callable($controller)) {
                throw new \InvalidArgumentException(sprintf('The controller for URI "%s" is not callable: ', $request->getPathInfo()) . $this->getControllerError($controller));
            }

            return $controller;
        }

        if (\is_object($controller)) {
            if (!\is_callable($controller)) {
                throw new \InvalidArgumentException(sprintf('The controller for URI "%s" is not callable: ', $request->getPathInfo()) . $this->getControllerError($controller));
            }

            return $controller;
        }

        if (\function_exists($controller)) {
            return $controller;
        }

        try {
            $callable = $this->createController($controller);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException(sprintf('The controller for URI "%s" is not callable: ', $request->getPathInfo()) . $e->getMessage(), 0, $e);
        }

        if (!\is_callable($callable)) {
            throw new \InvalidArgumentException(sprintf('The controller for URI "%s" is not callable: ', $request->getPathInfo()) . $this->getControllerError($callable));
        }

        return $callable;
    }

    protected function instantiateController(string $class)
    {
        return new $class();
    }

    protected function createController(string $controller)
    {
        if (!str_contains($controller, '::')) {
            $controller = $this->instantiateController($controller);
            /** @var AbstractAjaxController $controller */
            $controller->setRequest($this->request);

            if (!\is_callable($controller)) {
                throw new \InvalidArgumentException($this->getControllerError($controller));
            }

            return $controller;
        }
        [$class, $method] = explode('::', $controller, 2);

        try {
            $controller = [$this->instantiateController($class), $method];
        } catch (\Error | \LogicException $e) {
            try {
                if ((new \ReflectionMethod($class, $method))->isStatic()) {
                    return $class . '::' . $method;
                }
            } catch (\ReflectionException $reflectionException) {
                throw $e;
            }

            throw $e;
        }

        if (!\is_callable($controller)) {
            throw new \InvalidArgumentException($this->getControllerError($controller));
        }

        return $controller;
    }

    private function getControllerError($callable): string
    {
        return 'CONTROLLER_ERROR';
    }
}