<?php


namespace Melodyx\Ajax;


use Melodyx\Ajax\DependencyInjection\AjaxExtension;

class AjaxBundle extends \Symfony\Component\HttpKernel\Bundle\Bundle
{
    public function getContainerExtension()
    {
        return new AjaxExtension();
    }
}