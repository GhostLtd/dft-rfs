<?php


namespace App\Form\Admin\DomesticSurvey\Edit;


use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Entity\Distance;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DayStop as DayStopForms;
use App\Form\DomesticSurvey\DaySummary as DaySummaryForms;
use App\Form\ValueUnitType;
use App\Utility\HazardousGoodsHelper;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractStopType extends AbstractType implements DataMapperInterface
{
    private $translator;
    private $hazardousGoodsHelper;

    private $dataClass;

    public function __construct(TranslatorInterface $translator, HazardousGoodsHelper $hazardousGoodsHelper)
    {
        $this->translator = $translator;
        $this->hazardousGoodsHelper = $hazardousGoodsHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->dataClass = $options['data_class'];
        $builder->setDataMapper($this);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $isNorthernIreland = $this->isNorthernIreland($event->getData());

            $transferChoices = array_merge(array_flip(array_map(function ($v) {
                return "Yes, via " . strtolower($this->translator->trans($v));
            }, array_flip(Day::TRANSFER_CHOICES))), [
                'No' => false,
            ]);

            $event->getForm()
                ->add('originLocation', Gds\InputType::class, [
                    'label_attr' => ['class' => 'govuk-label--s'],
                ])
                ->add('goodsTransferredFrom', Gds\ChoiceType::class, [
                    'label' => 'Were goods loaded?',
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'choices' => $transferChoices,
                    'expanded' => false,
                ])
                ->add('destinationLocation', Gds\InputType::class, [
                    'label_attr' => ['class' => 'govuk-label--s'],
                ])
                ->add('goodsTransferredTo', Gds\ChoiceType::class, [
                    'label' => 'Were goods unloaded?',
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'choices' => $transferChoices,
                    'expanded' => false,
                ]);

            if ($this->dataClass === DaySummary::class) {
                $event->getForm()->add('furthestStop', Gds\InputType::class, [
                    'label_attr' => ['class' => 'govuk-label--s'],
                ]);
            }

            // border crossing
            if ($isNorthernIreland) {
                $event->getForm()
                    ->add('borderCrossingLocation', Gds\InputType::class, [
                        'label' => 'domestic.day-stop.border-crossing.border-crossing-location.label',
                        'label_attr' => ['class' => 'govuk-label--s'],
                    ]);
            }

            switch ($this->dataClass) {
                case DaySummary::class :
                    $event->getForm()
                        ->add('distance_travelled', DaySummaryForms\DistanceTravelledType::class, [
                            'inherit_data' => true,
                            'label' => false,
                        ]);
                    break;
                case DayStop::class :
                    $event->getForm()
                        ->add('distanceTravelled', ValueUnitType::class, [
                            'label' => "domestic.day-stop.distance-travelled.distance-travelled.label",
                            'label_attr' => ['class' => 'govuk-label--s'],
                            'value_options' => DayStopForms\DistanceTravelledType::VALUE_OPTIONS,
                            'unit_options' => DayStopForms\DistanceTravelledType::UNIT_OPTIONS,
                            'data_class' => Distance::class,
                        ]);
                    break;
            }

            [$goodsChoices, $goodsChoiceOptions] = AbstractGoodsDescription::getFormChoicesAndOptions($this->dataClass === DaySummary::class);
            [$hazardousChoices, $hazardousChoiceOptions] = $this->hazardousGoodsHelper->getFormChoicesAndOptions(true, true, false);
            [$cargoChoices, $cargoChoiceOptions] = CargoType::getFormChoicesAndOptions();

            $hazardousChoices = ['' => null] + $hazardousChoices;
            $cargoChoices = ['' => null] + $cargoChoices;

            $emptyKey = AbstractGoodsDescription::GOODS_DESCRIPTION_TRANSLATION_PREFIX . AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY;
            $goodsChoiceOptions[$emptyKey] = array_merge(
                ($goodsChoiceOptions[$emptyKey] ?? []), [
                    'conditional_hide_form_names' => [
                        'hazardousGoodsCode', 'cargoTypeCode', 'goodsWeight', 'numberOfStops',
                    ],
                ]);

            $event->getForm()
                ->add('goodsDescription', Gds\ChoiceType::class, [
                    'choices' => $goodsChoices,
                    'choice_options' => $goodsChoiceOptions,
                    'label' => "Goods carried",
                    'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                ])
                ->add('goodsDescriptionOther', Gds\InputType::class, [
                    'label' => false,
                    'help' => "goods.description.help.other",
                ])
                ->add('hazardousGoodsCode', Gds\ChoiceType::class, [
                    'expanded' => false,
                    'choices' => $hazardousChoices,
                    'choice_options' => $hazardousChoiceOptions,
                    'label' => "Were these goods dangerous or hazardous?",
                    'label_attr' => ['class' => 'govuk-label--s'],
                    'label_html' => true,
                ])
                ->add('cargoTypeCode', Gds\ChoiceType::class, [
                    'expanded' => false,
                    'choices' => $cargoChoices,
                    'choice_options' => $cargoChoiceOptions,
                    'label' => "How were the goods carried?",
                    'label_attr' => ['class' => 'govuk-label--s'],
                ]);
            switch ($this->dataClass) {
                case DaySummary::class :
                    $event->getForm()
                        ->add('goodsWeight', DaySummaryForms\GoodsWeightType::class, [
                            'inherit_data' => true,
                            'label' => false,
                        ])
                        ->add('numberOfStops', DaySummaryForms\NumberOfStopsType::class, [
                            'inherit_data' => true,
                            'label' => 'Number of stops',
                            'label_attr' => ['class' => 'govuk-label--m'],
                        ]);
                    break;
                case DayStop::class :
                    $event->getForm()
                        ->add('goodsWeight', DayStopForms\GoodsWeightType::class, [
                            'inherit_data' => true,
                            'label' => false,
                        ]);
                    break;
            }

            $event->getForm()
                ->add('submit', Gds\ButtonType::class, [
                    'label' => 'Save changes',
                ])
                ->add('cancel', Gds\ButtonType::class, [
                    'label' => 'Cancel',
                    'attr' => ['class' => 'govuk-button--secondary'],
                ]);
        });
    }

    protected function isNorthernIreland($stop) {
        return $stop->getDay()->getResponse()->getSurvey()->getIsNorthernIreland();
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setAllowedValues('data_class', [DayStop::class, DaySummary::class]);

        $resolver->setDefault('is_northern_ireland', function(FormInterface $form) {
            return $this->isNorthernIreland($form->getData());
        });

        $resolver->setDefault('validation_groups', function(FormInterface $form) {
            /** @var DayStop $stop */
            $stop = $form->getData();
            $groups = ['admin-day-stop'];

            if ($this->isNorthernIreland($stop)) {
                $groups[] = 'admin-day-stop-ni';
            }

            if ($stop->getGoodsLoaded()) {
                $groups[] = 'admin-day-stop-loaded';
            }

            if ($stop->getGoodsUnloaded()) {
                $groups[] = 'admin-day-stop-unloaded';
            }

            if ($stop->getGoodsDescription() !== AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY) {
                $groups[] = 'admin-day-stop-not-empty';
            }

            return $groups;
        });
    }

    public function mapDataToForms($viewData, $forms)
    {
        if ($viewData === null || !$viewData instanceof $this->dataClass) {
            return false;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $forms = iterator_to_array($forms);
        foreach ($forms as $name => $field) {
            switch ($name) {
                case 'goodsTransferredFrom':
                    $forms[$name]->setData($viewData->getGoodsLoaded() !== false ? $viewData->getGoodsTransferredFrom() : false);
                    break;
                case 'goodsTransferredTo':
                    $forms[$name]->setData($viewData->getGoodsUnloaded() !== false ? $viewData->getGoodsTransferredTo() : false);
                    break;
                case 'submit':
                case 'cancel':
                    break;
                default:
                    $forms[$name]->setData($accessor->getValue($viewData, $name));
            }
        }
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $forms = iterator_to_array($forms);
        $accessor = PropertyAccess::createPropertyAccessor();

        /** @var DayStop | DaySummary $viewData */
        foreach ($forms as $name => $field) {
            switch ($name) {
                case 'goodsTransferredFrom':
                    $viewData->setGoodsLoaded($field->getData() !== false);
                    $viewData->setGoodsTransferredFrom($field->getData() !== false ? $field->getData() : null);
                    break;
                case 'goodsTransferredTo':
                    $viewData->setGoodsUnloaded($field->getData() !== false);
                    $viewData->setGoodsTransferredTo($field->getData() !== false ? $field->getData() : null);
                    break;
                case 'wasAtCapacity':
                case 'wasLimitedBy':
                case 'submit':
                case 'cancel':
                    break;
                default:
                    $accessor->setValue($viewData, $name, $field->getData());
            }
        }

        $wasAtCapacity = $forms['wasAtCapacity']->getData();
        $wasLimitedBy = $forms['wasLimitedBy']->getData();

        if ($wasAtCapacity === false) {
            $viewData->setWasLimitedBySpace(false);
            $viewData->setWasLimitedByWeight(false);
        } else {
            $viewData->setWasLimitedBy($wasLimitedBy);
        }

        if ($viewData->getGoodsDescription() === AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY) {
            $viewData
                ->setCargoTypeCode(null)
                ->setHazardousGoodsCode(null)
                ->setWeightOfGoodsCarried(null)
                ->setWasAtCapacity(null)
                ->setWasLimitedByWeight(null)
                ->setWasLimitedBySpace(null);
        }

        $viewData->setWasAtCapacity($wasAtCapacity);
    }
}