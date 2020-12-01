<?php

namespace App\Form;

use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractVehicleBodyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "{$options['translation_entity_key']}.vehicle-body";
        $builder
            ->add('bodyType', Gds\ChoiceType::class, [
                'property_path' => $options['property_path'],
                'choices' => Vehicle::BODY_CONFIGURATION_CHOICES,
                'label' => "{$translationKeyPrefix}.body-type.label",
                'help' => "{$translationKeyPrefix}.body-type.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            "translation_entity_key",
            "property_path",
        ]);
        $resolver->setAllowedValues("translation_entity_key", ['domestic.survey-response', 'international.vehicle']);
    }
}
