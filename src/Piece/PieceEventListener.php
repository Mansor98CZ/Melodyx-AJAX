<?php


namespace Melodyx\Ajax\Piece;


use Symfony\Component\HttpKernel\Event\ViewEvent;
use Twig\Environment;

class PieceEventListener
{
    public function __construct(
        private Environment $environment
    )
    {
    }

    public function onPieceRender(PieceEvent $event)
    {
        $this->environment->render('', []);
        dump('LISTENER HAVE EVENT');
        dump($event);
    }
}