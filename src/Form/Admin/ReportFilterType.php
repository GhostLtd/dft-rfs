<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportFilterType extends AbstractType
{
    public const TYPE_CSRGT = 'csrgt';
    public const TYPE_CSRGT_GB = 'csrgt-gb';
    public const TYPE_CSRGT_NI = 'csrgt-ni';
    public const TYPE_IRHS = 'irhs';
    public const TYPE_PRE_ENQUIRY = 'pre-enquiry';
    public const TYPE_RORO = 'roro';

    public const CHOICE_TYPES = [
        'CSRGT (All)' => self::TYPE_CSRGT,
        'CSRGT (GB)' => self::TYPE_CSRGT_GB,
        'CSRGT (NI)' => self::TYPE_CSRGT_NI,
        'IRHS' => self::TYPE_IRHS,
        'Pre-Enquiry' => self::TYPE_PRE_ENQUIRY,
        'RoRo' => self::TYPE_RORO,
    ];

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $quarters = [
            "Quarter 1" => 1,
            "Quarter 2" => 2,
            "Quarter 3" => 3,
            "Quarter 4" => 4,
        ];
        $years = range($options['minYear'], $options['maxYear']);

        $choices = array_filter(self::CHOICE_TYPES, fn(string $choice) => !in_array($choice, $options['excludeChoices']));

        $builder
            ->add('type', Gds\ChoiceType::class, [
                'choices' => $choices,
                'expanded' => false,
                'label' => 'Survey type',
            ])
            ->add('quarter', Gds\ChoiceType::class, [
                'choices' => $quarters,
                'expanded' => false,
                'label' => 'Quarter',
            ])
            ->add('year', Gds\ChoiceType::class, [
                'choices' => array_combine($years, $years),
                'expanded' => false,
                'label' => 'Year',
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Apply',
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'maxYear' => intval((new \DateTime())->format('Y')) + 1,
            'excludeChoices' => [],
        ]);

        $resolver->setRequired([
            'minYear',
        ]);

        $resolver->setAllowedTypes('excludeChoices', 'array');

        $resolver->setNormalizer('minYear',
            fn(Options $options, ?int $minYear) => $minYear ?? intval((new \DateTime())->format('Y'))
        );
    }
}
