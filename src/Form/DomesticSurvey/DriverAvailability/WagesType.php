<?php

namespace App\Form\DomesticSurvey\DriverAvailability;

use App\Entity\Domestic\DriverAvailability;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WagesType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.driver-availability.wages";

        $builder
            ->setDataMapper(new WagesTypeMapper())

            ->add('haveWagesIncreased', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.haveWagesIncreased',
                'label' => "{$translationKeyPrefix}.have-wages-increased.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "{$translationKeyPrefix}.have-wages-increased.help",
                'choices' => DriverAvailability::YES_NO_PLAN_TO_CHOICES,
                'choice_options' => [
                    DriverAvailability::YES_NO_PLAN_TO_TRANSLATION_PREFIX . 'yes' => [
                        'conditional_form_name' => 'increased_wages_yes',
                    ],
                ],
            ])
            ->add('increased_wages_yes', FormType::class, [
                'inherit_data' => true,
                'label' => false,
            ])
            ;
        $builder->get('increased_wages_yes')
            ->add('averageWageIncrease', Gds\DecimalMoneyType::class, [
                'property_path' => 'survey.driverAvailability.averageWageIncrease',
                'label' => "{$translationKeyPrefix}.average-wage-increase.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.average-wage-increase.help",
                'attr' => ['class' => 'govuk-input--width-5'],
                'divisor' => 100,
            ])
            ->add('wageIncreasePeriod', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.wageIncreasePeriod',
                'label' => "{$translationKeyPrefix}.wage-increase-period.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "{$translationKeyPrefix}.wage-increase-period.help",
                'choices' => DriverAvailability::WAGE_INCREASE_PERIOD_CHOICES,
                'choice_options' => [
                    DriverAvailability::WAGE_INCREASE_PERIOD_TRANSLATION_PREFIX . 'other' => [
                        'conditional_form_name' => 'wageIncreasePeriodOther',
                    ],
                ],
            ])
            ->add('wageIncreasePeriodOther', Gds\InputType::class, [
                'property_path' => 'survey.driverAvailability.wageIncreasePeriodOther',
                'label' => "{$translationKeyPrefix}.wage-increase-period-other.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.wage-increase-period-other.help",
            ])
            ->add('reasonsForWageIncrease', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.reasonsForWageIncrease',
                'multiple' => true,
                'label' => "{$translationKeyPrefix}.reasons-for-wage-increase.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "{$translationKeyPrefix}.reasons-for-wage-increase.help",
                'choices' => DriverAvailability::WAGE_INCREASE_REASON_CHOICES,
                'choice_options' => [
                    DriverAvailability::WAGE_INCREASE_REASON_TRANSLATION_PREFIX . 'other' => ['conditional_form_name' => 'reasonsForWageIncreaseOther'],
                ],
            ])
            ->add('reasonsForWageIncreaseOther', Gds\InputType::class, [
                'property_path' => 'survey.driverAvailability.reasonsForWageIncreaseOther',
                'label' => "{$translationKeyPrefix}.reasons-for-wage-increase-other.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.reasons-for-wage-increase-other.help",
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['driver-availability.wages']
        ]);
    }
}
