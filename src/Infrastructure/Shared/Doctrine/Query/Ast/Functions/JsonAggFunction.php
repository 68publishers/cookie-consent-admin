<?php

declare(strict_types=1);

namespace App\Infrastructure\Shared\Doctrine\Query\Ast\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\OrderByClause;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

final class JsonAggFunction extends FunctionNode
{
    /** @var array<Node> */
    private array $fields = [];

    private ?OrderByClause $orderBy = null;

    private bool $isDistinct = false;

    /**
     * @throws QueryException
     */
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $lexer = $parser->getLexer();

        if ($lexer->isNextToken(TokenType::T_DISTINCT)) {
            $parser->match(TokenType::T_DISTINCT);

            $this->isDistinct = true;
        }

        do {
            $this->fields[] = $parser->StringPrimary();
        } while ($lexer->isNextToken(TokenType::T_COMMA) && null === $parser->match(TokenType::T_COMMA));

        if ($lexer->isNextToken(TokenType::T_ORDER)) {
            $this->orderBy = $parser->OrderByClause();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $fields = implode(', ', array_map(static function (Node $node) use ($sqlWalker) {
            return $sqlWalker->walkStringPrimary($node);
        }, $this->fields));

        return sprintf(
            'json_agg(%s%s%s)',
            ($this->isDistinct ? 'DISTINCT ' : ''),
            1 < count($this->fields) ? '(' . $fields . ')' : $fields,
            ($this->orderBy ? $sqlWalker->walkOrderByClause($this->orderBy) : ''),
        );
    }
}
