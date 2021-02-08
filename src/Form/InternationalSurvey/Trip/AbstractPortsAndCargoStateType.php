<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\CrossingRoute;
use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Trip\DataMapper\PortsAndCargoStateDataMapper;
use App\Form\LimitedByType;
use App\Repository\International\CrossingRouteRepository;
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
    const CHOICE_YES = 'common.choices.boolean.yes';
    const CHOICE_NO = 'common.choices.boolean.no';
    const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    const DIRECTION_OUTBOUND = 'outbound';
    const DIRECTION_RETURN = 'return';

    protected CrossingRouteRepository $crossingRouteRepository;

    protected string $direction;
    protected bool $isOutbound;

    /** @var CrossingRoute[] $ports */
    protected array $ports;

    public function __construct(CrossingRouteRepository $crossingRouteRepository)
    {
        $this->crossingRouteRepository = $crossingRouteRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->setDirectionAndLoadPorts($options['direction']);
        $prefix = "international.trip.{$this->direction}-ports";

        $builder->setDataMapper(new PortsAndCargoStateDataMapper($this->direction, $this->ports));

        $this->addPortField($builder, $prefix);

        $this->addCargoStateFields($builder, $this->direction);
    }

    protected function addPortField(FormBuilderInterface $builder, string $prefix)
    {
        $builder->add('ports', Gds\ChoiceType::class, [
            'expanded' => false,
            'label' => "{$prefix}.ports.label",
            'help' => "{$prefix}.ports.help",
            'attr' => ['class' => 'govuk-input--width-10'],
            'label_attr' => ['class' => 'govuk-label--s'],
            'choices' => $this->getPortChoices($prefix),
        ]);
    }

    public function setDirectionAndLoadPorts(string $direction):void
    {
        $this->direction = $direction;
        $this->isOutbound = $this->direction === AbstractPortsAndCargoStateType::DIRECTION_OUTBOUND;

        $this->ports = $this->crossingRouteRepository
            ->createQueryBuilder('c')
            ->orderBy('c.' . ($this->isOutbound ? 'ukPort' : 'foreignPort'), 'ASC')
            ->getQuery()
            ->execute();
    }

    protected function getPortChoices(string $prefix): array
    {
        $choices = array_combine(
            array_map(
                fn(CrossingRoute $c) => $this->isOutbound ?
                    "{$c->getUkPort()} – {$c->getForeignPort()}" :
                    "{$c->getForeignPort()} – {$c->getUkPort()}",
                $this->ports
            ),
            array_map(fn(CrossingRoute $c) => $c->getId(), $this->ports)
        );

        return ["{$prefix}.ports.choice-placeholder" => null] + $choices;
    }

    protected function addCargoStateFields(FormBuilderInterface $builder, $direction)
    {
        $prefix = "international.trip.{$direction}-cargo-state";

        $emptyPrefix = "{$prefix}.was-empty";

        $limitedPrefix = "{$prefix}.was-limited";
        $atCapacityPrefix = "{$prefix}.was-at-capacity";

        $builder
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

        $resolver->setAllowedValues('direction', [self::DIRECTION_OUTBOUND, self::DIRECTION_RETURN]);
        $resolver->setDefaults([
            'data_class' => Trip::class,
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
