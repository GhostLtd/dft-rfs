<?php

namespace App\Form\Domestic;

use App\Entity\DomesticSurveyResponse;
use App\Entity\SurveyResponseTrait;
use App\Entity\Vehicle;
use App\Entity\VehicleTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class VehicleAxleConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event)
        {
            if ($event->getData()) {
                /** @var VehicleTrait $vehicle */
                $vehicle = $event->getData()->getVehicle();
                $event->getForm()
                    ->add('axleConfiguration', Gds\ChoiceType::class, [
                        'property_path' => 'vehicle.axleConfiguration',
                        'choices' => Vehicle::AXLE_CONFIGURATION_CHOICES[$vehicle->getTrailerConfiguration()],
                        'label' => 'survey.domestic.forms.vehicle-axle-configuration.axle-configuration.label',
                        'label_attr' => ['class' => 'govuk-label--m'],
                    ])
                ;
            }

        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DomesticSurveyResponse::class,
        ]);
    }
}
