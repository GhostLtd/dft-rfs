<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\Distance;
use App\Entity\International\Trip;
use App\Form\Admin\InternationalSurvey\DataMapper\TripDataMapper;
use App\Form\InternationalSurvey\Trip\CountriesTransittedType;
use Ghost\GovUkFrontendBundle\Form\Type\ValueUnitType;
use App\Utility\PortsDataFactory;
use App\Utility\PortsDataSet;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TripType extends AbstractType
{
    public const CARGO_STATE_EMPTY = 'empty';
    public const CARGO_STATE_PART_FILLED = 'part-filled';
    public const CARGO_STATE_CAPACITY_SPACE = 'space';
    public const CARGO_STATE_CAPACITY_WEIGHT = 'weight';
    public const CARGO_STATE_CAPACITY_BOTH = 'weight-and-space';

    public const CARGO_STATE_CHOICES = [
        'Empty' => self::CARGO_STATE_EMPTY,
        'Partially filled' => self::CARGO_STATE_PART_FILLED,
        'At capacity by space' => self::CARGO_STATE_CAPACITY_SPACE,
        'At capacity by weight' => self::CARGO_STATE_CAPACITY_WEIGHT,
        'At capacity by space and weight' => self::CARGO_STATE_CAPACITY_BOTH,
    ];

    public const CHOICE_YES = 'common.choices.boolean.yes';
    public const CHOICE_NO = 'common.choices.boolean.no';
    public const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    /** @var PortsDataSet[] $portsDataSets */
    protected array $portsDataSets;

    public function __construct(protected PortsDataFactory $portsDataFactory)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $this->portsDataSets = [
            'outbound' => $this->portsDataFactory->getData('outbound'),
            'return' => $this->portsDataFactory->getData('return')
        ];

        $this->addDirectionalFields($builder, 'outbound');
        $this->addDirectionalFields($builder, 'return');

        $builder
            ->setDataMapper(new TripDataMapper($this->portsDataSets))


            ->add('origin', Gds\InputType::class, [
                'label' => 'Initial origin',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('destination', Gds\InputType::class, [
                'label' => 'Final destination',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
        ;

        $this
            ->addFieldSet($builder, 'otherDetails', 'Other Details')
            ->add('roundTripDistance', ValueUnitType::class, [
                'label' => "Round trip distance",
                'label_attr' => ['class' => 'govuk-label--s'],
                'value_form_type' => Gds\DecimalType::class,
                'value_options' => [
                    'label' => 'Distance',
                    'precision' => 10,
                    'scale' => 1,
                    'attr' => ['class' => 'govuk-input--width-5'],
                ],
                'unit_options' => [
                    'label' => 'common.value-unit.unit',
                    'choices' => Distance::UNIT_CHOICES,
                ],
                'data_class' => Distance::class,
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var Trip $trip */
            $trip = $event->getData();
            $event->getForm()->get('otherDetails')
                ->add('axle_config', Gds\ChoiceType::class, [
                    'choices' => array_merge(['Unchanged' => null], $trip->getTrailerSwapChoices()),
                    'expanded' => false,
                    'label' => "New trailer axle configuration",
                    'label_attr' => ['class' => 'govuk-label--s'],
                ]);
            if ($trip->canChangeBodyType()) {
                $event->getForm()->get('otherDetails')
                    ->add('body_type', Gds\ChoiceType::class, [
                        'choices' => array_merge(['Unchanged' => false], $trip->getChangeBodyTypeChoices()),
                        'expanded' => false,
                        'label' => "New trailer body type",
                        'label_attr' => ['class' => 'govuk-label--s'],
                    ]);
            }

            // Vehicle weights are in this event listener so that we maintain a sensible order
            $event->getForm()->get('otherDetails')
                ->add('gross_weight', Gds\IntegerType::class, [
                    'label' => "Gross vehicle weight (kg)",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'attr' => ['class' => 'govuk-input--width-10'],
                    'suffix' => 'kg',
                ])
                ->add('carrying_capacity', Gds\IntegerType::class, [
                    'label' => "Carrying capacity (kg)",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'attr' => ['class' => 'govuk-input--width-10'],
                    'suffix' => 'kg',
                ])
                ;
        });


        $builder
            ->add('countries', CountriesTransittedType::class, [
                'inherit_data' => true,
                'label' => 'Which countries were travelled through?',
                'label_attr' => ['class' => 'govuk-label--m govuk-label--underline'],
                'attr' => ['class' => 'govuk-!-margin-bottom-9'],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    protected function addDirectionalFields(FormBuilderInterface $builder, string $direction): void
    {
        $portsData = $this->portsDataSets[$direction];

        $builder
            ->add("{$direction}Date", Gds\DateType::class, [
                'label' => "Date",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add("{$direction}Ports", Gds\ChoiceType::class, [
                'label' => 'Ports',
                'choices' => $portsData->getPortChoices(),
                'expanded' => false,
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;

        $builder->add("{$direction}CargoState", Gds\ChoiceType::class, [
            'choices' => self::CARGO_STATE_CHOICES,
            'expanded' => false,
            'label' => 'Cargo state',
            'label_attr' => ['class' => 'govuk-label--s'],
        ]);
    }

    protected function addFieldSet(FormBuilderInterface $builder, string $fieldName, string $label): FormBuilderInterface
    {
        $builder->add($fieldName, Gds\FieldsetType::class, [
            'label' => $label,
            'label_attr' => ['class' => 'govuk-label--m govuk-fieldset__legend--underline'],
            'attr' => ['class' => 'govuk-!-margin-bottom-9'],
        ]);

        return $builder->get($fieldName);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'validation_groups' => ['admin_trip'],
        ]);
    }
}
