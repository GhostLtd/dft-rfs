<?php

namespace App\Form\DomesticSurvey\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\LimitedByType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsWeightType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.day-stop.goods-weight";
        $builder
            ->add('weightOfGoodsCarried', Gds\IntegerType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods-carried.label",
                'help' => "{$translationKeyPrefix}.weight-of-goods-carried.help",
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
                'suffix' => 'kg',
            ])
            ->add('wasAtCapacity', Gds\ChoiceType::class, [
                'label' => "{$translationKeyPrefix}.was-at-capacity.label",
                'help' => "{$translationKeyPrefix}.was-at-capacity.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => [
                    "common.choices.boolean.yes" => true,
                    "common.choices.boolean.no" => false,
                ],
                'choice_options' => [
                    "common.choices.boolean.yes" => ['conditional_form_name' => 'wasLimitedBy'],
                ],
                'choice_value' => function(?bool $x) {
                    if ($x === null) {
                        return null;
                    }

                    return $x ? 'yes' : 'no';
                },
            ])
            ->add('wasLimitedBy', LimitedByType::class, [
                'label' => "{$translationKeyPrefix}.was-limited-by.label",
                'help' => "{$translationKeyPrefix}.was-limited-by.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DayStop::class,
            'validation_groups' => ['goods-weight', 'at-capacity'],
        ]);
    }
}
