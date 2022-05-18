<?php

namespace App\Form\Admin\PreEnquiry;

use App\Form\Admin\ReportFilterType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PreEnquiryReportFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $years = range($options['minYear'], $options['maxYear']);

        $choices = array_filter(ReportFilterType::CHOICE_TYPES, function(string $choice) use ($options) {
            return !in_array($choice, $options['excludeChoices']);
        });

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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'maxYear' => intval((new \DateTime())->format('Y')) + 1,
            'excludeChoices' => [],
        ]);

        $resolver->setRequired([
            'minYear',
        ]);

        $resolver->setAllowedTypes('excludeChoices', 'array');

        $resolver->setNormalizer('minYear', function(Options $options, ?int $minYear) {
            return $minYear === null ?
                intval((new \DateTime())->format('Y')) :
                $minYear;
        });
    }
}