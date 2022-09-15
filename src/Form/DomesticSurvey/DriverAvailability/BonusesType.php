<?php


namespace App\Form\DomesticSurvey\DriverAvailability;


use App\Entity\CurrencyOrPercentage;
use App\Entity\Domestic\DriverAvailability;
use App\Form\ValueUnitType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
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
                        'conditional_form_name' => 'paid_bonus_yes',
                    ],
                ],
            ])
            ->add('paid_bonus_yes', FormType::class, [
                'inherit_data' => true,
                'label' => false,
            ]);

        $builder->get('paid_bonus_yes')
            ->add('reasonsForBonuses', Gds\ChoiceType::class, [
                'property_path' => 'survey.driverAvailability.reasonsForBonuses',
                'label' => "{$translationKeyPrefix}.reasons-for-bonuses.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.reasons-for-bonuses.help",
                'choices' => DriverAvailability::BONUS_REASON_CHOICES,
                'multiple' => true,
            ])
            ->add('averageBonus', Gds\MoneyType::class, [
                'property_path' => 'survey.driverAvailability.averageBonus',
                'label' => "{$translationKeyPrefix}.average-bonus.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.average-bonus.help",
                'attr' => ['class' => 'govuk-input--width-5'],
                'divisor' => 100,
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