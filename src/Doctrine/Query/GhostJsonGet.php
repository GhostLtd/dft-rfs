<?php

namespace App\Doctrine\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\TokenType;

/**
 * Named as to indicate clearly that this isn't a MySQL (or indeed Sqlite)
 * function, and so that it doesn't clash with any genuine functions.
 *
 * Background:
 *
 * - JSON_EXTRACT acts differently in MySQL and Sqlite where it returns a quoted and
 *    unquoted value respectively
 * - JSON_UNQUOTE does not exist in Sqlite
 * - However, the ->> operator does exist in both and appears to function the same in both
 *   [And in Mysql, a ->> b is synonymous with JSON_UNQUOTE(JSON_EXTRACT(a, b))]
 *
 *   See: https://www.sqlite.org/json1.html#jptr
 *
 * This DQL function maps to the ->> operator so that we can use a single DQL function to
 * achieve what is needed without needing separate MySQL and Sqlite code paths.
 */
class GhostJsonGet extends FunctionNode
{
    protected Node $doc;
    protected array $paths = [];

    public function parse(Parser $parser): void
    {
        $parser->match(TokenType::T_IDENTIFIER);
        $parser->match(TokenType::T_OPEN_PARENTHESIS);

        $this->doc = $parser->StringPrimary();

        $parser->match(TokenType::T_COMMA);

        $this->paths[] = $parser->StringPrimary();

        while($parser->getLexer()->isNextToken(TokenType::T_COMMA)) {
            $parser->match(TokenType::T_COMMA);
            $this->paths[] = $parser->StringPrimary();
        }

        $parser->match(TokenType::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker): string
    {
        $doc = $sqlWalker->walkStringPrimary($this->doc);
        $paths = join(', ', array_map($sqlWalker->walkStringPrimary(...), $this->paths));

        return "{$doc} ->> {$paths}";
    }
}
