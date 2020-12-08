<?php

namespace App\Form\InternationalSurvey\Trip;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = 'international.trip.dates';

        $this->addDateField($builder, 'outboundDate', "{$translationKeyPrefix}.outbound-date");
        $this->addDateField($builder, 'returnDate', "{$translationKeyPrefix}.return-date");
    }

    protected function addDateField(FormBuilderInterface $builder, string $fieldName, string $prefix) {
        $builder
            ->add($fieldName, Gds\DateType::class, [
                'label' => "{$prefix}.label",
                'help' => "{$prefix}.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['trip_dates'],
        ]);
    }
}