<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\Field\ChoiceFilter;

class QaChoiceFilter extends ChoiceFilter
{
    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, string|bool $formData): QueryBuilder
    {
        return $formData === false ?
            $queryBuilder->andWhere("{$this->getPropertyPath()} IS NULL") :
            parent::addFilterCondition($queryBuilder, $formData);
    }
}
