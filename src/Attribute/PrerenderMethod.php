<?php


namespace Melodyx\Ajax\Attribute;

use Attribute;

#[Attribute]
class PrerenderMethod
{
    public function __construct(private string $methodName)
    {
    }

    /**
     * @return string
     */
    public function getMethodName(): string
    {
        return $this->methodName;
    }
}