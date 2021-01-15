<?php


namespace App\Form\Admin\DomesticSurvey\Edit;


use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Form\DomesticSurvey\DayStop\CargoTypeType;
use App\Form\DomesticSurvey\DayStop\DistanceTravelledType;
use App\Form\DomesticSurvey\DayStop\GoodsWeightType;
use App\Form\DomesticSurvey\DayStop\HazardousGoodsType;
use App\Utility\HazardousGoodsHelper;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Contracts\Translation\TranslatorInterface;

class DayStopType extends AbstractType implements DataMapperInterface
{
    private $translator;
    private $hazardousGoodsHelper;

    public function __construct(TranslatorInterface $translator, HazardousGoodsHelper $hazardousGoodsHelper)
    {
        $this->translator = $translator;
        $this->hazardousGoodsHelper = $hazardousGoodsHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $transferChoices = array_merge(array_flip(array_map(function($v){
            return "Yes, via " . strtolower($this->translator->trans($v));
        }, array_flip(Day::TRANSFER_CHOICES))), [
            'No' => false,
        ]);

        [$goodsChoices, $goodsChoiceOptions] = AbstractGoodsDescription::getFormChoicesAndOptions(false);
        [$hazardousChoices, $hazardousChoiceOptions] = $this->hazardousGoodsHelper->getFormChoicesAndOptions(true, true, false);
        [$cargoChoices, $cargoChoiceOptions] = CargoType::getFormChoicesAndOptions();

        $builder
            ->setDataMapper($this)
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
            ])
            ;

        // border crossing
        $builder->add('distance_travelled', DistanceTravelledType::class, [
                'inherit_data' => true,
                'label' => false,
                'is_child_form' => true,
            ])
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
            ])

            ->add('goods_weight', GoodsWeightType::class, [
                'inherit_data' => true,
                'label' => false,
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DayStop::class,
        ]);
    }

    public function mapDataToForms($viewData, $forms)
    {
        if ($viewData === null) {
            return false;
        }

        if (!$viewData instanceof DayStop) {
            return false;
        }

        $accessor = PropertyAccess::createPropertyAccessor();

        $forms = iterator_to_array($forms);
        foreach ($forms as $name => $field) {
            switch ($name) {
                case 'goodsTransferredFrom' :
                    $forms[$name]->setData($viewData->getGoodsLoaded() !== false ? $viewData->getGoodsTransferredFrom() : false);
                    break;
                case 'goodsTransferredTo' :
                    $forms[$name]->setData($viewData->getGoodsUnloaded() !== false ? $viewData->getGoodsTransferredTo() : false);
                    break;
                default :
                    $forms[$name]->setData($accessor->getValue($viewData, $name));
            }
        }
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $forms = iterator_to_array($forms);

        $viewData = (new DayStop())
            ->setGoodsLoaded($forms['goodsTransferredFrom'] !== false)
            ->setGoodsTransferredFrom($forms['goodsTransferredFrom'] !== false ? $forms['goodsTransferredFrom'] : null);
    }
}