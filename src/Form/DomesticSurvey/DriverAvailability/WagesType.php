<?php


namespace App\Form\DomesticSurvey\DriverAvailability;


use App\Entity\CurrencyOrPercentage;
use App\Entity\Domestic\DriverAvailability;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WagesType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.driver-availability.wages";

        $builder
            ->setDataMapper(new WagesTypeMapper())

            ->add('haveWagesIncreased', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.haveWagesIncreased',
                'label' => "{$translationKeyPrefix}.have-wages-increased.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.have-wages-increased.help",
                'choices' => DriverAvailability::YES_NO_PLAN_TO_CHOICES,
                'choice_options' => [
                    DriverAvailability::YES_NO_PLAN_TO_TRANSLATION_PREFIX . 'no' => [
                        'conditional_hide_form_names' => ['reasonsForWageIncrease', 'averageWageIncrease'],
                    ],
                ],
            ])
            ->add('averageWageIncrease', ValueUnitType::class, [
                'property_path' => 'survey.driverAvailability.averageWageIncrease',
                'label' => "{$translationKeyPrefix}.average-wage-increase.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.average-wage-increase.help",
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
            ->add('reasonsForWageIncrease', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.reasonsForWageIncrease',
                'multiple' => true,
                'label' => "{$translationKeyPrefix}.reasons-for-wage-increase.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.reasons-for-wage-increase.help",
                'choices' => DriverAvailability::WAGE_INCREASE_REASON_CHOICES,
                'choice_options' => [
                    DriverAvailability::WAGE_INCREASE_REASON_TRANSLATION_PREFIX . 'other' => ['conditional_form_name' => 'reasonsForWageIncreaseOther'],
                ],
            ])
            ->add('reasonsForWageIncreaseOther', Gds\InputType::class, [
                'property_path' => 'survey.driverAvailability.reasonsForWageIncrease',
                'label' => "{$translationKeyPrefix}.reasons-for-wage-increase-other.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.reasons-for-wage-increase-other.help",
            ])
        ;

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['driver-availability.wages']
        ]);
    }
}