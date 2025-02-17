<?php

namespace App\ListPage\Field;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;

class MultiFieldTextFilter extends TextFilter implements FilterableInterface
{
    public function __construct(string $label, private readonly array $propertyPaths, protected array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, "", $cellOptions);
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        $trimmed = trim($formData);
        return $queryBuilder
            ->andWhere(new Expr\Orx(array_map(fn($p) => "{$p} LIKE :{$this->getId()}", $this->propertyPaths)))
            ->setParameter($this->getId(), "%{$trimmed}%");
    }
}