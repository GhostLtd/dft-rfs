<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Trip\DataMapper\PortsAndCargoStateDataMapper;
use App\Form\LimitedByType;
use App\Utility\PortsDataSet;
use App\Utility\PortsDataFactory;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class AbstractPortsAndCargoStateType extends AbstractType
{
    public const CHOICE_YES = 'common.choices.boolean.yes';
    public const CHOICE_NO = 'common.choices.boolean.no';
    public const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    public const DIRECTION_OUTBOUND = 'outbound';
    public const DIRECTION_RETURN = 'return';
    protected PortsDataSet $portsData;

    public function __construct(protected PortsDataFactory $portsHelper)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $direction = $options['direction'];
        $this->portsData = $this->portsHelper->getData($direction);

        $booleanChoiceMapper = fn($x) => match ($x) {
            true => 'yes',
            false => 'no',
            null => null,
        };

        $builder
            ->setDataMapper(new PortsAndCargoStateDataMapper($direction, $this->portsData->getPorts()))
            ->add('ports', Gds\ChoiceType::class, [
                'expanded' => false,
                'label' => $options['ports_label'],
                'help' => $options['ports_help'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => $this->portsData->getPortChoices(),
            ])
            ->add("wasAtCapacity", Gds\ChoiceType::class, [
                'label' => $options['cargo_at_capacity_label'],
                'help' => $options['cargo_at_capacity_help'],
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => self::CHOICES,
                'choice_options' => [
                    self::CHOICE_NO => [
                        'conditional_form_name' => 'wasEmpty',
                    ],
                    self::CHOICE_YES => [
                        'conditional_form_name' => 'wasLimitedBy',
                    ],
                ],
                'choice_value' => $booleanChoiceMapper,
                'constraints' => new NotNull([
                    'message' => $options['at_capacity_null_message'],
                    'groups' => ['trip_outbound_cargo_state', 'trip_return_cargo_state'],
                ]),
            ])
            ->add("wasEmpty", Gds\ChoiceType::class, [
                'label' => $options['cargo_empty_label'],
                'help' => $options['cargo_empty_help'],
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => self::CHOICES,
                'choice_value' => $booleanChoiceMapper,
            ])
            ->add('wasLimitedBy', LimitedByType::class, [
                'label' => $options['cargo_limited_label'],
                'help' => $options['cargo_limited_help'],
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'direction',

            'cargo_at_capacity_label',
            'cargo_at_capacity_help',
            'cargo_empty_label',
            'cargo_empty_help',
            'cargo_limited_label',
            'cargo_limited_help',

            'ports_label',
            'ports_help',
        ]);

        $resolver->setAllowedValues('direction', [self::DIRECTION_OUTBOUND, self::DIRECTION_RETURN]);
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'constraints' => [
                new Callback([
                    'callback' => $this->validateCapacity(...),
                    'groups' => ['trip_outbound_cargo_state', 'trip_return_cargo_state'],
                ])
            ],
            'at_capacity_null_message' => 'common.choice.not-null',
        ]);
    }

    public function validateCapacity(Trip $trip, ExecutionContextInterface $context): void
    {
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
