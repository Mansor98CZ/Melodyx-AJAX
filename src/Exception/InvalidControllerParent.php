<?php


namespace Melodyx\Ajax\Exception;


use Melodyx\Ajax\AbstractAjaxController;
use Throwable;

class InvalidControllerParent extends AbstractException
{
    public function __construct(string $controllerName)
    {
        parent::__construct('Controller: "' . $controllerName . '" must extends: "' . AbstractAjaxController::class . '"');
    }
}