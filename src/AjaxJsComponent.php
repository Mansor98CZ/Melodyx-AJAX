<?php


namespace Melodyx\Ajax;


class AjaxJsComponent
{
    public static function render(): string
    {
        $js = '<script>'.  file_get_contents(__DIR__ . '/Resources/js/melodyx.ajax.js') . '</script>';
        return $js;
    }
}