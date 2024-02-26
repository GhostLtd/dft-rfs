<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class TextFilter extends Simple implements FilterableInterface
{
    public function __construct(string $label, string $propertyPath, protected array $formOptions = [], array $cellOptions = [])
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
        return Gds\InputType::class;
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, $formData): QueryBuilder
    {
        $trimmed = trim($formData);
        return $queryBuilder
            ->andWhere("{$this->getPropertyPath()} LIKE :{$this->getId()}")
            ->setParameter($this->getId(), "%{$trimmed}%");
    }
}