<?php

namespace App\Form\InternationalSurvey\Vehicle;

use App\Entity\International\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class VehicleRegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "international.vehicle.vehicle-registration";
        $builder
            ->add('registrationMark', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.registration-mark.label",
                'help' => "{$translationKeyPrefix}.registration-mark.help",
                'label_attr' => ['class' => 'govuk-label--l'],
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Vehicle::class,
        ]);
    }
}
