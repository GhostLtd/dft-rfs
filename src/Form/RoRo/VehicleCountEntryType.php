<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\VehicleCount;
use Ghost\GovUkFrontendBundle\Form\Type\NumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleCountEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
            /** @var VehicleCount $countryVehicleCount */
            $countryVehicleCount = $event->getData();
            $event->getForm()
                ->add('vehicleCount', NumberType::class, [
                    'attr' => ['class' => 'govuk-input--width-5'],
                    'label' => $countryVehicleCount->getLabel(),
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'translation_domain' => false, // Already translated via VehicleCountHelper
                ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => VehicleCount::class,
            'label' => false,
        ]);
    }
}