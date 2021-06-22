<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportFilterType extends AbstractType
{
    public const TYPE_CSRGT = 'csrgt';
    public const TYPE_CSRGT_GB = 'csrgt-gb';
    public const TYPE_CSRGT_NI = 'csrgt-ni';
    public const TYPE_IRHS = 'irhs';

    public const CHOICE_TYPES = [
        'CSRGT (All)' => self::TYPE_CSRGT,
        'CSRGT (GB)' => self::TYPE_CSRGT_GB,
        'CSRGT (NI)' => self::TYPE_CSRGT_NI,
        'IRHS' => self::TYPE_IRHS,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $quarters = [
            "Quarter 1" => 1,
            "Quarter 2" => 2,
            "Quarter 3" => 3,
            "Quarter 4" => 4,
        ];
        $years = range($options['minYear'], $options['maxYear']);

        $builder
            ->add('type', Gds\ChoiceType::class, [
                'choices' => self::CHOICE_TYPES,
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'maxYear' => intval((new \DateTime())->format('Y')),
        ]);

        $resolver->setRequired([
            'minYear',
        ]);
    }
}