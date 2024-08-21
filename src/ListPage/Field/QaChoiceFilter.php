<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;

class QaChoiceFilter extends ChoiceFilter
{
    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        return $formData === false ?
            $queryBuilder->andWhere("{$this->getPropertyPath()} IS NULL") :
            parent::addFilterCondition($queryBuilder, $formData);
    }
}