<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\VehicleCount;
use Ghost\GovUkFrontendBundle\Form\Type\IntegerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleCountEntryType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($options) {
            /** @var VehicleCount $countryVehicleCount */
            $countryVehicleCount = $event->getData();

            $help = $options['help_options'][$countryVehicleCount->getId()] ?? null;

            $countryCode = $countryVehicleCount->getCountryCode();
            $label = $countryVehicleCount->getLabel();

            if ($countryCode) {
                $label .= " ($countryCode)";
            }

            $event->getForm()
                ->add('vehicleCount', IntegerType::class, [
                    'attr' => ['class' => 'govuk-input--width-5'],
                    'help' => $help,
                    'label' => $label,
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'translation_domain' => false, // Already translated via VehicleCountHelper
                ]);
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VehicleCount::class,
            'label' => false,
            'help_options' => [],
        ]);
    }
}
