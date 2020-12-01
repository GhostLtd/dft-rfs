<?php

namespace App\Form;

use App\Entity\Vehicle;
use App\Entity\VehicleTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractVehicleAxleConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options)
        {
            $translationKeyPrefix = "{$options['translation_entity_key']}.vehicle-axle-configuration";

            if ($event->getData()) {
                /** @var VehicleTrait $vehicle */
                $vehicle = $event->getData()->getVehicle();

                $event->getForm()
                    ->add('axleConfiguration', Gds\ChoiceType::class, [
                        'property_path' => $options['property_path'],
                        'choices' => Vehicle::AXLE_CONFIGURATION_CHOICES[$vehicle->getTrailerConfiguration()],
                        'label' => "{$translationKeyPrefix}.axle-configuration.label",
                        'help' => "{$translationKeyPrefix}.axle-configuration.help",
                        'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                    ])
                ;
            }

        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            "translation_entity_key",
        ]);

        $resolver->setDefaults([
            'property_path' => 'axleConfiguration',
        ]);

        $resolver->setAllowedValues("translation_entity_key", ['domestic.survey-response', 'international.vehicle']);
    }
}