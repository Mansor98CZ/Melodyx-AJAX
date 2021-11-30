<?php


namespace Melodyx\Ajax\Exception;


use Throwable;

class ReservedPayloadName extends AbstractException
{
    public function __construct(string $key)
    {
        parent::__construct('Payload key: "' . $key . '" is reserved by package');
    }
}