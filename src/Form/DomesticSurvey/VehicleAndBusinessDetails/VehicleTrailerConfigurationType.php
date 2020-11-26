<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VehicleTrailerConfigurationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.survey-response.vehicle-trailer-configuration";
        $builder
            ->add('trailerConfiguration', Gds\ChoiceType::class, [
                'property_path' => 'vehicle.trailerConfiguration',
                'choices' => Vehicle::TRAILER_CONFIGURATION_CHOICES,
                'label' => "{$translationKeyPrefix}.trailer-configuration.label",
                'help' => "{$translationKeyPrefix}.trailer-configuration.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'label_is_page_heading' => true,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
        ]);
    }
}
