<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Form\InternationalSurvey\Trip\DataMapper\CargoStateDataMapper;
use App\Form\LimitedByType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractCargoStateType extends AbstractType
{
    const CHOICE_YES = 'common.choices.boolean.yes';
    const CHOICE_NO = 'common.choices.boolean.no';
    const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $direction = $options['direction'];
        $prefix = "international.trip.{$direction}-cargo-state";

        $cargoStatePrefix = "{$prefix}.cargo-state";
        $emptyPrefix = "{$prefix}.was-empty";
        $limitedPrefix = "{$prefix}.was-limited";

        $builder
            ->setDataMapper(new CargoStateDataMapper($direction))
            ->add('cargoStateFieldset', Gds\FieldsetType::class, [
                'label' => "{$cargoStatePrefix}.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => "{$cargoStatePrefix}.help",
                'attr' => ['class' => 'govuk-input--5'],
            ]);

        $fieldSet = $builder->get('cargoStateFieldset');

        $fieldSet
            ->add("wasEmpty", Gds\ChoiceType::class, [
                'label' => "{$emptyPrefix}.label",
                'help' => "{$emptyPrefix}.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => self::CHOICES,
                'choice_options' => [
                    self::CHOICE_NO => [
                        'conditional_form_name' => 'wasLimitedBy',
                    ],
                ],
            ])
            ->add('wasLimitedBy', LimitedByType::class, [
                'label' => "{$limitedPrefix}.label",
                'help' => "{$limitedPrefix}.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'direction',
        ]);

        $resolver->setAllowedValues('direction', ['outbound', 'return']);
    }
}
