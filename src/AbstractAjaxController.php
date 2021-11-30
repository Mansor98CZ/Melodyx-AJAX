<?php


namespace Melodyx\Ajax;


use Melodyx\Ajax\Exception\ReservedPayloadName;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractAjaxController extends AbstractController
{
    private Request $request;

    private array $payload = [];

    private array $redraw = [];

    private const RESERVED_PAYLOAD_KEYS = [
        'pieces'
    ];

    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    protected function isAjax(): bool
    {
        return $this->request->isXmlHttpRequest();
    }

    public function onShutdown(): Response
    {
        if (!empty($this->redraw)) {
            $this->payload['pieces'] = $this->redraw;
        }

        if (!$this->isAjax()) {
            return new Response(status: 404);
        }

        return $this->json($this->payload);
    }

    protected function addPayload(string $key, mixed $data): void
    {
        if (in_array($key, self::RESERVED_PAYLOAD_KEYS)) {
            throw new ReservedPayloadName($key);
        }
        $this->payload[$key] = $data;
    }

    protected function addPiece(string $name, mixed $content): void
    {
        $this->redraw[$name] = $content;
    }

    protected function redrawPiece(string $pieceName): void
    {
        $this->redraw[] = $pieceName;
    }

}