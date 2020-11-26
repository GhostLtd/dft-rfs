<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
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
            $translationKeyPrefix = "domestic.survey-response.vehicle-axle-configuration";

            if ($event->getData()) {
                /** @var VehicleTrait $vehicle */
                $vehicle = $event->getData()->getVehicle();

                $event->getForm()
                    ->add('axleConfiguration', Gds\ChoiceType::class, [
                        'property_path' => 'vehicle.axleConfiguration',
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
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
        ]);
    }
}
