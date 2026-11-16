<?php

namespace App\Doctrine\Query;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\Phase2OptimizableConditional;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * IF(condition, then, else) for DQL; either branch may be NULL.
 *
 * Emits MySQL's IF() on MySQL (production, and the MySQL test suite) and an equivalent
 * CASE WHEN elsewhere (SQLite, used by the main test suite, has no IF()). Parsing matches
 * DoctrineExtensions\Query\Mysql\IfElse, which this replaces, so that tests on either database
 * accept exactly the DQL that production does.
 */
class IfElse extends FunctionNode
{
    private ConditionalExpression|Phase2OptimizableConditional $condition;
    private ?Node $then = null;
    private ?Node $else = null;

    #[\Override]
    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);
        $this->condition = $parser->ConditionalExpression();

        $parser->match(TokenType::T_COMMA);
        $this->then = $this->parseBranch($parser);

        $parser->match(TokenType::T_COMMA);
        $this->else = $this->parseBranch($parser);

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    #[\Override]
    public function getSql(SqlWalker $sqlWalker): string
    {
        $condition = $sqlWalker->walkConditionalExpression($this->condition);
        $then = $this->walkBranch($sqlWalker, $this->then);
        $else = $this->walkBranch($sqlWalker, $this->else);

        return $sqlWalker->getConnection()->getDatabasePlatform() instanceof AbstractMySQLPlatform
            ? "IF({$condition}, {$then}, {$else})"
            : "CASE WHEN {$condition} THEN {$then} ELSE {$else} END";
    }

    private function parseBranch(Parser $parser): ?Node
    {
        if ($parser->getLexer()->isNextToken(TokenType::T_NULL)) {
            $parser->match(TokenType::T_NULL);
            return null;
        }

        return $parser->ArithmeticExpression();
    }

    private function walkBranch(SqlWalker $sqlWalker, ?Node $branch): string
    {
        return $branch === null ? 'NULL' : $sqlWalker->walkArithmeticPrimary($branch);
    }
}
