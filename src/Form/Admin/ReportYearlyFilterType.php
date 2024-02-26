<?php

namespace App\Form\Admin;

use App\Form\Admin\ReportFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReportYearlyFilterType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $years = range($options['minYear'], $options['maxYear']);

        $choices = array_filter(ReportFilterType::CHOICE_TYPES, fn(string $choice) => !in_array($choice, $options['excludeChoices']));

        $builder
            ->add('type', Gds\ChoiceType::class, [
                'choices' => $choices,
                'expanded' => false,
                'label' => 'Survey type',
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

        $resolver->setNormalizer('minYear', fn(Options $options, ?int $minYear) =>
            $minYear ?? intval((new \DateTime())->format('Y'))
        );
    }
}
