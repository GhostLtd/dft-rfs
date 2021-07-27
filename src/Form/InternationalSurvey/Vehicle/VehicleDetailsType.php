<?php

namespace App\Form\InternationalSurvey\Vehicle;

use App\Entity\International\Vehicle as InternationalVehicle;
use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class VehicleDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "international.vehicle.vehicle-details";
        $builder
            ->add('registrationMark', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.registration-mark.label",
                'help' => "{$translationKeyPrefix}.registration-mark.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('operationType', Gds\ChoiceType::class, [
                'label' => "{$translationKeyPrefix}.operation-type.label",
                'help' => "{$translationKeyPrefix}.operation-type.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => Vehicle::OPERATION_TYPE_CHOICES,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => InternationalVehicle::class,
            'validation_groups' => ['vehicle_registration', 'vehicle_operation_type'],
        ]);
    }
}
