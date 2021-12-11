<?php


namespace Melodyx\Ajax;


use Twig\Environment;
use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

class AjaxJsComponent
{
    public function __construct(private Environment $environment)
    {
    }

    public function render(): string
    {
        $js = file_get_contents(__DIR__ . '/Resources/js/melodyx.ajax.js');
        $render = $this->environment->render('@Ajax/error.html.twig', [
            'js' => $js
        ]);
        return $render;
    }
}