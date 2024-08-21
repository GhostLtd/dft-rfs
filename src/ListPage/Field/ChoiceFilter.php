<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class ChoiceFilter extends Simple implements FilterableInterface
{
    public function __construct(string $label, string $propertyPath, protected array $choices, private array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, $propertyPath, $cellOptions);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge([
            'placeholder' => $this->getLabel(),
            'choices' => $this->choices,
            'expanded' => false,
        ], $this->formOptions);
    }

    #[\Override]
    public function getFormClass(): string
    {
        return Gds\ChoiceType::class;
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        return $queryBuilder
            ->andWhere("{$this->getPropertyPath()} = :{$this->getId()}")
            ->setParameter($this->getId(), $formData);
    }
}