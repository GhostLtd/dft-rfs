<?php

namespace App\ListPage\Domestic\Field;

use Doctrine\ORM\QueryBuilder;
use Ghost\GovUkCoreBundle\ListPage\Field\FilterableInterface;
use Ghost\GovUkCoreBundle\ListPage\Field\Simple;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;

class QualityFlagsChoiceFilter extends Simple implements FilterableInterface
{
    public const string CHOICE_ANY = 'any';
    public const string CHOICE_UNSEEN = 'unseen';
    public const string CHOICE_SEEN = 'seen';
    public const string CHOICE_NONE = 'none';

    public function __construct(string $label, private array $formOptions = [], array $cellOptions = [])
    {
        parent::__construct($label, 'response.qualityFlagCount', $cellOptions);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge([
            'expanded' => false,
            'placeholder' => $this->label,
            'choices' => [
                'With flags (any)' => self::CHOICE_ANY,
                'Unreviewed flags' => self::CHOICE_UNSEEN,
                'Reviewed flags' => self::CHOICE_SEEN,
                'No flags' => self::CHOICE_NONE,
            ],
        ], $this->formOptions);
    }

    #[\Override]
    public function getFormClass(): string
    {
        return ChoiceType::class;
    }

    #[\Override]
    public function addFilterCondition(QueryBuilder $queryBuilder, string $formData): QueryBuilder
    {
        return match ($formData) {
            self::CHOICE_ANY => $queryBuilder
                ->andWhere('response.qualityFlagCount > 0'),

            self::CHOICE_UNSEEN => $queryBuilder
                ->andWhere('response.qualityFlagCount > 0')
                ->andWhere('response.qualityFlagsSeen IS NULL OR response.qualityFlagsSeen = :qualityFlagsSeen')
                ->setParameter('qualityFlagsSeen', 0),

            self::CHOICE_SEEN => $queryBuilder
                ->andWhere('response.qualityFlagCount > 0')
                ->andWhere('response.qualityFlagsSeen = :qualityFlagsSeen')
                ->setParameter('qualityFlagsSeen', 1),

            self::CHOICE_NONE => $queryBuilder
                ->andWhere('response.qualityFlagCount IS NULL OR response.qualityFlagCount = 0'),

            default => $queryBuilder,
        };
    }
}
