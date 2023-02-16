<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\Survey;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleCountsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $vehicleCountClass = "govuk-grid-column-one-half govuk-grid-column-one-third-from-desktop";
        $builder
            ->add('countryVehicleCounts', CollectionType::class, [
                'attr' => ['class' => 'govuk-!-margin-bottom-5'],
                'label' => 'roro.survey.vehicle-counts.country.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'entry_type' => VehicleCountEntryType::class,
            ])
            ->add('otherVehicleCounts', CollectionType::class, [
                'attr' => ['class' => 'govuk-!-margin-bottom-5'],
                'label' => 'roro.survey.vehicle-counts.other.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'entry_type' => VehicleCountEntryType::class,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
        ]);
    }
}