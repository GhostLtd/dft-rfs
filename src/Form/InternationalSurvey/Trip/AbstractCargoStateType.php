<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Trip\DataMapper\CargoStateDataMapper;
use App\Form\LimitedByType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

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

        $emptyPrefix = "{$prefix}.was-empty";

        $limitedPrefix = "{$prefix}.was-limited";
        $atCapacityPrefix = "{$prefix}.was-at-capacity";

        $builder
            ->setDataMapper(new CargoStateDataMapper($direction))
            ->add("wasAtCapacity", Gds\ChoiceType::class, [
                'label' => "{$atCapacityPrefix}.label",
                'help' => "{$atCapacityPrefix}.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => self::CHOICES,
                'choice_options' => [
                    self::CHOICE_NO => [
                        'conditional_form_name' => 'wasEmpty',
                    ],
                    self::CHOICE_YES => [
                        'conditional_form_name' => 'wasLimitedBy',
                    ],
                ],
                'constraints' => new NotNull([
                    'message' => 'common.choice.not-null',
                    'groups' => ['trip_outbound_cargo_state', 'trip_return_cargo_state'],
                ]),
            ])
            ->add("wasEmpty", Gds\ChoiceType::class, [
                'label' => "{$emptyPrefix}.label",
                'help' => "{$emptyPrefix}.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => self::CHOICES,
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

        $resolver->setDefaults([
            'constraints' => [
                new Callback([
                    'callback' => [$this, 'validateCapacity'],
                    'groups' => ['trip_outbound_cargo_state', 'trip_return_cargo_state'],
                ])
            ],
        ]);
    }

    public function validateCapacity(Trip $trip, ExecutionContextInterface $context) {
        $form = $context->getRoot();
        if ($form instanceof FormInterface) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $direction = $form->getConfig()->getOption('direction');

            $atCapacity = $form->get('wasAtCapacity');
            $wasLimitedBySpace = $accessor->getValue($trip, "{$direction}WasLimitedBySpace");
            $wasLimitedByWeight = $accessor->getValue($trip, "{$direction}WasLimitedByWeight");

            if ($atCapacity->getData() === true && !$wasLimitedBySpace && !$wasLimitedByWeight) {
                $context->buildViolation('international.trip.cargo-state-how-at-capacity')->atPath('wasLimitedBy')->addViolation();
            }
        }
    }
}
