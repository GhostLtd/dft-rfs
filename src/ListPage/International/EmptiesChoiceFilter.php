<?php

namespace App\ListPage\International;

use App\Entity\International\Action;
use App\ListPage\Field\FilterableInterface;
use App\ListPage\Field\Simple;
use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class EmptiesChoiceFilter extends Simple implements FilterableInterface
{
    public function __construct(string $label, private array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, 'emptiesCount', $cellOptions);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge([
            'placeholder' => $this->getLabel(),
            'choices' => ['With empties' => 'Yes', 'No empties' => 'No'],
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
        if ($formData === 'Yes') {
            $queryBuilder->andHaving('emptiesCount > 0');
        } else {
            $queryBuilder->andHaving('emptiesCount = 0');
        }

        return $queryBuilder;
    }
}