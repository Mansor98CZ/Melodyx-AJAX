<?php


namespace Melodyx\Ajax\Twig;


use Melodyx\Ajax\Exception\IdentifierAlreadyExists;
use Twig\Compiler;
use Twig\Node\BlockNode;
use Twig\Node\Node;

class PieceTagNode extends BlockNode
{
    public function __construct(string $name, private Node $body, int $lineno, private string $nameOfBlock, string $tag = null)
    {
        parent::__construct($name, $body, $lineno, $tag);
    }

    public function compile(Compiler $compiler): void
    {
        $id = 'piece-' . $this->nameOfBlock;
        if (PieceIdentifierCollector::hasIdentifier($this->nameOfBlock)) {
            throw new IdentifierAlreadyExists($this->nameOfBlock);
        }
        PieceIdentifierCollector::addIdentifier($this->nameOfBlock);
        $compiler->addDebugInfo($this);
        $compiler->write('echo "<div id=\"' . $id . '\">";');
        $compiler->indent()
            ->subcompile($this->body);
        $compiler->outdent();
        $compiler->write('echo "</div>";');
    }
}