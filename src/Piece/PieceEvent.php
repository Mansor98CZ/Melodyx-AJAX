<?php


namespace Melodyx\Ajax\Piece;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class PieceEvent extends RequestEvent
{
    public function __construct(HttpKernelInterface $kernel, Request $request, private Response $handleResponse,?int $requestType)
    {
        parent::__construct($kernel, $request, $requestType);
    }
}