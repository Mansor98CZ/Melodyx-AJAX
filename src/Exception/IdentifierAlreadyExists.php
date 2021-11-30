<?php


namespace Melodyx\Ajax\Exception;


use Throwable;

class IdentifierAlreadyExists extends AbstractException
{
    public function __construct(string $identifier)
    {
        parent::__construct('Identifier with name: "' . $identifier . '"');
    }
}