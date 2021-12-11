<?php


namespace Melodyx\Ajax\Twig;


use Melodyx\Ajax\AjaxJsComponent;
use Twig\Environment;
use Twig\TwigFunction;

class AjaxExtension extends \Twig\Extension\AbstractExtension
{
    public function __construct(private AjaxJsComponent $ajaxJsComponent)
    {
    }

    public function getTokenParsers()
    {
        return [
            new PieceTagTokenParser()
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('melodyx_ajax', [$this->ajaxJsComponent, 'render']),
            new TwigFunction('melodyx_ajax_handle', [$this, 'link'])
        ];
    }

    public function link(string $handlerName): string
    {
        return '?handle=' . $handlerName;
    }
}