<?php


namespace Melodyx\Ajax\Twig;


use Melodyx\Ajax\AjaxJsComponent;
use Twig\TwigFunction;

class AjaxExtension extends \Twig\Extension\AbstractExtension
{
    public function getTokenParsers()
    {
       return [
         new PieceTagTokenParser()
       ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('melodyx_ajax', [AjaxJsComponent::class , 'render']),
            new TwigFunction('melodyx_ajax_handle', [$this, 'link'])
        ];
    }

    public function link(string $handlerName): string
    {
        return '?handle=' . $handlerName;
    }
}