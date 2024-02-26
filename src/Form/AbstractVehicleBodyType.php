<?php

namespace App\Form;

use App\Entity\Vehicle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractVehicleBodyType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "{$options['translation_entity_key']}.vehicle-body";
        $builder
            ->add('bodyType', Gds\ChoiceType::class, [
                'property_path' => $options['property_path'],
                'choices' => Vehicle::BODY_CONFIGURATION_CHOICES,
                'label' => "{$translationKeyPrefix}.body-type.label",
                'help' => "{$translationKeyPrefix}.body-type.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'translation_entity_key',
        ]);

        $resolver->setDefaults([
            'property_path' => null,
            'validation_groups' => ['vehicle_body_type']
        ]);

        $resolver->setAllowedValues("translation_entity_key", ['domestic.survey-response', 'international.vehicle']);
    }
}
