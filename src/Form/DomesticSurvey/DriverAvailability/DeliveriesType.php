<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use App\Entity\Domestic\DriverAvailability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DeliveriesType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.driver-availability.deliveries";

        $builder
            ->setDataMapper(new DeliveriesTypeMapper())

            ->add('numberOfLorriesOperated', Gds\IntegerType::class, [
                'property_path' => 'survey.driverAvailability.numberOfLorriesOperated',
                'label' => "{$translationKeyPrefix}.number-of-lorries-operated.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.number-of-lorries-operated.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('numberOfParkedLorries', Gds\IntegerType::class, [
                'property_path' => 'survey.driverAvailability.numberOfParkedLorries',
                'label' => "{$translationKeyPrefix}.number-of-parked-lorries.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.number-of-parked-lorries.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
            ->add('hasMissedDeliveries', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.hasMissedDeliveries',
                'label' => "{$translationKeyPrefix}.has-missed-deliveries.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "{$translationKeyPrefix}.has-missed-deliveries.help",
                'choices' => DriverAvailability::MISSING_DELIVERY_CHOICES,
                'choice_options' => [
                    DriverAvailability::MISSING_DELIVERY_TRANSLATION_PREFIX.'yes' => ['conditional_form_name' => 'numberOfMissedDeliveries'],
                ],
            ])
            ->add('numberOfMissedDeliveries', Gds\IntegerType::class, [
                'property_path' => 'survey.driverAvailability.numberOfMissedDeliveries',
                'label' => "{$translationKeyPrefix}.number-of-missed-deliveries.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.number-of-missed-deliveries.help",
                'attr' => ['class' => 'govuk-input--width-5'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['driver-availability.deliveries']
        ]);
    }
}
