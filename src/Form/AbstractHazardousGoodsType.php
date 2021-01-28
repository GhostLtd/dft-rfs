<?php

namespace App\Form;

use App\Entity\HazardousGoods;
use App\Entity\HazardousGoodsTrait;
use App\Entity\International\Action;
use App\Utility\HazardousGoodsHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\NotNull;

abstract class AbstractHazardousGoodsType extends AbstractType implements DataMapperInterface
{
    protected $hazardousGoodsHelper;

    public function __construct(HazardousGoodsHelper $hazardousGoodsHelper)
    {
        $this->hazardousGoodsHelper = $hazardousGoodsHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        [$choices, $choiceOptions] = $this->hazardousGoodsHelper->getFormChoicesAndOptions(false, true, true, false);

        $translationKeyPrefix = "{$options['translation_entity_key']}.hazardous-goods";
        $builder
            ->setDataMapper($this)
            ->add('isHazardousGoods', Gds\ChoiceType::class, [
                'label' => false,
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => [
                    "common.choices.boolean.yes" => true,
                    "common.choices.boolean.no" => false,
                ],
                'choice_options' => [
                    "common.choices.boolean.yes" => ['conditional_form_name' => 'hazardousGoodsCode'],
                ],
                'mapped' => false,
                'constraints' => new NotNull(['message' => 'common.choice.not-null', 'groups' => 'is-hazardous-goods']),
            ])
            ->add('hazardousGoodsCode', Gds\ChoiceType::class, [
                'choices' => $choices,
                'choice_options' => $choiceOptions,
                'label' => "{$translationKeyPrefix}.hazardous-goods-code.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.hazardous-goods-code.help",
                'constraints' => new NotNull(['message' => 'common.choice.not-null', 'groups' => 'hazardous-goods-code']),
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => function(FormInterface $form){
                $groups = ['is-hazardous-goods'];
                if ($form->get('isHazardousGoods')->getData() === true) {
                    $groups[] = 'hazardous-goods-code';
                }
                return $groups;
            },
        ]);
        $resolver->setRequired(["translation_entity_key"]);
    }

    public function mapDataToForms($viewData, $forms)
    {
        /** @var HazardousGoodsTrait $viewData */
        if (null === $viewData) {
            return;
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['isHazardousGoods'])) {
            return;
        }

        $hazardousGoodsCode = $viewData->getHazardousGoodsCode();
        switch(true)
        {
            case null === $hazardousGoodsCode :
                $forms['isHazardousGoods']->setData(null);
                $forms['hazardousGoodsCode']->setData(null);
                break;

            case HazardousGoods::CODE_0_NOT_HAZARDOUS === $hazardousGoodsCode :
                $forms['isHazardousGoods']->setData(false);
                $forms['hazardousGoodsCode']->setData(null);
                break;

            default:
                $forms['isHazardousGoods']->setData(true);
                $forms['hazardousGoodsCode']->setData($viewData->getHazardousGoodsCode());

        }
    }

    public function mapFormsToData($forms, &$viewData)
    {
        /** @var HazardousGoodsTrait $viewData */
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['isHazardousGoods'])) {
            return;
        }

        $isHazardousGoods = $forms['isHazardousGoods']->getData();
        switch(true)
        {
            case null === $isHazardousGoods :
                $viewData->setHazardousGoodsCode(null);
                break;
            case false === $isHazardousGoods :
                $viewData->setHazardousGoodsCode(HazardousGoods::CODE_0_NOT_HAZARDOUS);
                break;
            case true === $isHazardousGoods :
                $viewData->setHazardousGoodsCode($forms['hazardousGoodsCode']->getData());
                break;
        }

    }

}
