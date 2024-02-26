<?php

namespace App\ListPage\Domestic\Field;


use App\ListPage\Field\FilterableInterface;
use App\ListPage\Field\Simple;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;

class DaysFilter extends Simple implements FilterableInterface
{
    public function __construct(string $label, private array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, null, $cellOptions);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge([
            'expanded' => false,
            'placeholder' => $this->label,
            'choices' => [
                'No days' => 0,
                '1+ days' => 1,
            ]
        ], $this->formOptions);
    }

    #[\Override]
    public function getFormClass(): string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        if (!isset($formData)) {
            return $queryBuilder;
        }
        // ensure the filter is being used in the right place
        if (!$queryBuilder->getQuery()->contains("LEFT JOIN day.stops stop")
            || !$queryBuilder->getQuery()->contains("LEFT JOIN day.summary summary")
        ) {
            return $queryBuilder;
        }

        if ($formData === 0) {
            return $queryBuilder
                ->andWhere($queryBuilder->expr()->andX(
                    "summary IS NULL",
                    "stop IS NULL"
                ));
        } else {
            return $queryBuilder
                ->andWhere($queryBuilder->expr()->orX(
                    "summary IS NOT NULL",
                    "stop IS NOT NULL"
                ));
        }
    }
}