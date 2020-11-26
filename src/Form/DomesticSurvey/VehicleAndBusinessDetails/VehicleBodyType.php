<?php

namespace App\Form\DomesticSurvey\VehicleAndBusinessDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class VehicleBodyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.survey-response.vehicle-body";
        $builder
            ->add('bodyType', Gds\ChoiceType::class, [
                'property_path' => 'vehicle.bodyType',
                'choices' => Vehicle::BODY_CONFIGURATION_CHOICES,
                'label' => "{$translationKeyPrefix}.body-type.label",
                'help' => "{$translationKeyPrefix}.body-type.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
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
