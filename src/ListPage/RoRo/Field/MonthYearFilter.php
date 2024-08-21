<?php

namespace App\ListPage\RoRo\Field;

use App\Form\Admin\RoRo\SurveyListField\MonthYearFilterType;
use App\ListPage\Field\FilterableInterface;
use App\ListPage\Field\Simple;
use Doctrine\ORM\QueryBuilder;

class MonthYearFilter extends Simple implements FilterableInterface
{
    public function __construct(string $label, string $propertyPath, private array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, $propertyPath, $cellOptions);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge([
            // no default options
        ], $this->formOptions);
    }

    #[\Override]
    public function getFormClass(): string
    {
        return MonthYearFilterType::class;
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        if (!isset($formData['year']) || !is_numeric($formData['year'])) {
            return $queryBuilder;
        }

        if (isset($formData['month'])) {
            $yearAndMonth = $formData['year'].'-'.$formData['month'];
            $start = \DateTime::createFromFormat('!Y-m', $yearAndMonth);
            $end = \DateTime::createFromFormat('!Y-m', $yearAndMonth);
            $end->modify('+1 month');
        } else {
            $year = $formData['year'];
            $start = \DateTime::createFromFormat('!Y', $year);
            $end = \DateTime::createFromFormat('!Y', $year);
            $end->modify('+1 year');
        }

        return $queryBuilder
            ->andWhere($queryBuilder->expr()->andX(
                "{$this->getPropertyPath()} >= :{$this->getId()}_start",
                "{$this->getPropertyPath()} < :{$this->getId()}_end"
            ))
            ->setParameter("{$this->getId()}_start", $start)
            ->setParameter("{$this->getId()}_end", $end);
    }
}