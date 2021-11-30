<?php


namespace Melodyx\Ajax\Twig;


use Twig\Error\Error;
use Twig\Error\SyntaxError;
use Twig\Node\IfNode;
use Twig\Node\Node;
use Twig\Node\PrintNode;
use Twig\Node\SetNode;
use Twig\Token;

class PieceTagTokenParser extends \Twig\TokenParser\AbstractTokenParser
{

    public function parse(Token $token): Node
    {
        $lineno = $token->getLine();
        $name = $this->parser->getVarName();
        $stream = $this->parser->getStream();
        $names = $this->parser->getExpressionParser()->parseAssignmentExpression();
        $nameOfBlock = $names->getNode(0)->getAttribute('name');
        $stream->expect(Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse([$this, 'decideBlockEnd']);
        $nodeContent = [];
        while (true) {
            $value = $stream->next()->getValue();
            if ($value === 'endpiece') {
                break;
            }
            $nodeContent[] = $value;
        }

        $stream->expect(Token::BLOCK_END_TYPE);
        return new PieceTagNode('piece', $body, $lineno, $nameOfBlock);
    }

    public function decideBlockEnd(Token $token): bool
    {
        return $token->test('endpiece');
    }

    public function getTag(): string
    {
        return 'piece';
    }
}