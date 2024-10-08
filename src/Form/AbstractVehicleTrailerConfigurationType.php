<?php

namespace App\Form;

use App\Entity\Vehicle;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractVehicleTrailerConfigurationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "{$options['translation_entity_key']}.vehicle-trailer-configuration";
        $builder
            ->add('trailerConfiguration', Gds\ChoiceType::class, [
                'property_path' => $options['property_path'],
                'choices' => Vehicle::TRAILER_CONFIGURATION_CHOICES,
                'label' => "{$translationKeyPrefix}.trailer-configuration.label",
                'help' => "{$translationKeyPrefix}.trailer-configuration.help",
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
            'validation_groups' => ['vehicle_trailer_configuration'],
        ]);

        $resolver->setAllowedValues("translation_entity_key", ['domestic.survey-response', 'international.vehicle']);
    }
}
