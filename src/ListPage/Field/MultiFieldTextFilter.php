<?php

namespace App\ListPage\Field;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\Field\TextFilter;

class MultiFieldTextFilter extends TextFilter
{
    public function __construct(string $label, private readonly array $propertyPaths, array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, "", $formOptions, $cellOptions);
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, string $formData): QueryBuilder
    {
        $trimmed = trim($formData);
        return $queryBuilder
            ->andWhere(new Expr\Orx(array_map(fn($p) => "{$p} LIKE :{$this->getId()}", $this->propertyPaths)))
            ->setParameter($this->getId(), "%{$trimmed}%");
    }
}
