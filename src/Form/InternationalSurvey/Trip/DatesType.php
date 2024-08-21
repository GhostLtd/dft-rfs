<?php

namespace App\Form\InternationalSurvey\Trip;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DatesType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->addDateField($builder, 'outboundDate', "international.trip.dates.outbound-date");
        $this->addDateField($builder, 'returnDate', "international.trip.dates.return-date");
    }

    protected function addDateField(FormBuilderInterface $builder, string $fieldName, string $prefix): void
    {
        $builder
            ->add($fieldName, Gds\DateType::class, [
                'label' => "{$prefix}.label",
                'help' => "{$prefix}.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['trip_dates'],
        ]);
    }
}
