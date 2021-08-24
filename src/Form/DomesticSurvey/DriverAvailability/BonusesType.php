<?php


namespace App\Form\DomesticSurvey\DriverAvailability;


use App\Entity\CurrencyOrPercentage;
use App\Entity\Domestic\DriverAvailability;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BonusesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.driver-availability.bonuses";

        $builder
            ->setDataMapper(new BonusesTypeMapper())

            ->add('hasPaidBonus', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.hasPaidBonus',
                'label' => "{$translationKeyPrefix}.has-paid-bonus.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.has-paid-bonus.help",
                'choices' => DriverAvailability::YES_NO_PLAN_TO_CHOICES,
                'choice_options' => [
                    DriverAvailability::YES_NO_PLAN_TO_TRANSLATION_PREFIX . 'yes' => [
                        'conditional_form_name' => 'averageBonus',
                    ],
                ],
            ])
            ->add('averageBonus', ValueUnitType::class, [
                'property_path' => 'survey.driverAvailability.averageBonus',
                'label' => "{$translationKeyPrefix}.average-bonus.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.average-bonus.help",
                'data_class' => CurrencyOrPercentage::class,
                'value_options' => [
                    'label' => 'Amount',
                    'attr' => ['class' => 'govuk-input--width-5']
                ],
                'unit_options' => [
                    'label' => 'Amount type',
                    'choices' => CurrencyOrPercentage::UNIT_CHOICES,
                ],
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['driver-availability.bonuses']
        ]);
    }
}