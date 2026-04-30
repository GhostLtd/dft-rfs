<?php

namespace App\ListPage\International;

use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class EmptiesChoiceFilter extends Simple
{
    public function __construct(string $label, private array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, 'emptiesCount', $cellOptions);
    }

    public function getFormOptions(): array
    {
        return array_merge([
            'placeholder' => $this->getLabel(),
            'choices' => ['With empties' => 'Yes', 'No empties' => 'No'],
            'expanded' => false,
        ], $this->formOptions);
    }

    public function getFormClass(): string
    {
        return Gds\ChoiceType::class;
    }

    public function addFilterCondition(QueryBuilder $queryBuilder, string $formData): QueryBuilder
    {
        if ($formData === 'Yes') {
            $queryBuilder->andHaving('emptiesCount > 0');
        } else {
            $queryBuilder->andHaving('emptiesCount = 0');
        }

        return $queryBuilder;
    }
}