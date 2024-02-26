<?php

namespace App\ListPage\Domestic\Field;

use App\ListPage\Field\ChoiceFilter;
use Doctrine\ORM\QueryBuilder;

class InPossessionFilter extends ChoiceFilter
{
    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        if ($formData === 'yes-exempt' || $formData === 'yes') {
            $qb = $queryBuilder
                ->andWhere("{$this->getPropertyPath()} = :{$this->getId()}")
                ->setParameter($this->getId(), 'yes');

            if ($formData === 'yes-exempt') {
                return $qb
                    ->andWhere('response.isExemptVehicleType = :isExempt')
                    ->setParameter('isExempt', 1);
            } else {
                return $qb
                    ->andWhere('(response.isExemptVehicleType IS NULL OR response.isExemptVehicleType = :isExempt)')
                    ->setParameter('isExempt', 0);
            }
        }

        return parent::addFilterCondition($queryBuilder, $formData);
    }
}