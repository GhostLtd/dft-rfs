<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\GroupSequence;

class NumberOfStopsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.day-summary.number-of-stops";

        $builder
            ->add('group', Gds\FieldsetType::class, [
                'label' => false,
            ]);

        $builder->get('group')
            ->add('numberOfStopsLoading', Gds\IntegerType::class, [
                'label' => "{$translationKeyPrefix}.number-of-stops-loading.label",
                'help' => "{$translationKeyPrefix}.number-of-stops-loading.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('numberOfStopsUnloading', Gds\IntegerType::class, [
                'label' => "{$translationKeyPrefix}.number-of-stops-unloading.label",
                'help' => "{$translationKeyPrefix}.number-of-stops-unloading.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('numberOfStopsLoadingAndUnloading', Gds\IntegerType::class, [
                'label' => "{$translationKeyPrefix}.number-of-stops-both.label",
                'help' => "{$translationKeyPrefix}.number-of-stops-both.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
            'validation_groups' => new GroupSequence(['day-summary.number-of-stops', 'day-summary.number-of-stops.total-stops']),
            'error_mapping' => [
                'number-of-stops' => 'group',
            ],
        ]);
    }
}
