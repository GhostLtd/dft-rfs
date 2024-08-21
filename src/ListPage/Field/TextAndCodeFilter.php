<?php

namespace App\ListPage\Field;

use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class TextAndCodeFilter extends Simple implements FilterableInterface
{
    protected string $codeId;

    /**
     * N.B. $propertyPath is the one used for ordering
     */
    public function __construct(string $label, string $propertyPath, protected string $codePropertyPath, protected array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, $propertyPath, $cellOptions);
        $this->codeId = self::generateId($label.' code');
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

        // To make sure things in our user-input aren't interpreted as LIKE pattern modifiers
        $escaped = addcslashes($trimmed, '%_');

        $cond = $queryBuilder->expr()->orX(
            $queryBuilder->expr()->like($this->getPropertyPath(), ':'.$this->getId()),
            $queryBuilder->expr()->eq($this->codePropertyPath, ':'.$this->codeId),
        );

        return $queryBuilder
            ->andWhere($cond)
            ->setParameter($this->getId(), "%{$escaped}%")
            ->setParameter($this->codeId, $trimmed);
    }
}