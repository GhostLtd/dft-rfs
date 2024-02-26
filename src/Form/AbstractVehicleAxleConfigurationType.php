<?php

namespace App\Form;

use App\Entity\Vehicle;
use App\Entity\VehicleInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractVehicleAxleConfigurationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options)
        {
            if ($event->getData()) {
                $vehicle = $this->getVehicle($event->getData());

                $event->getForm()
                    ->add('axleConfiguration', Gds\ChoiceType::class, [
                        'property_path' => $options['property_path'],
                        'choices' => Vehicle::AXLE_CONFIGURATION_CHOICES[$vehicle->getTrailerConfiguration()],
                        'label' => $options['axle_configuration_label'],
                        'help' => $options['axle_configuration_help'],
                        'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                    ])
                ;
            }
        });
    }

    abstract protected function getVehicle(mixed $formData): VehicleInterface;

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'axle_configuration_label',
            'axle_configuration_help',
        ]);

        $resolver->setDefaults([
            'property_path' => null,
            'validation_groups' => ['vehicle_axle_configuration'],
        ]);
    }
}
