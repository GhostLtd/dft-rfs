<?php

namespace App\Form;

use App\Entity\HazardousGoods;
use App\Entity\HazardousGoodsInterface;
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
    public function __construct(protected HazardousGoodsHelper $hazardousGoodsHelper)
    {}

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        [$choices, $choiceOptions] = $this->hazardousGoodsHelper->getFormChoicesAndOptions(false, true, true, false);

        $translationKeyPrefix = "{$options['translation_entity_key']}.hazardous-goods";
        $builder
            ->setDataMapper($this)
            ->add('isHazardousGoods', Gds\ChoiceType::class, [
                'label' => "{$translationKeyPrefix}.is-hazardous-goods.label",
                'help' => "{$translationKeyPrefix}.is-hazardous-goods.help",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'choices' => [
                    "common.choices.boolean.yes" => true,
                    "common.choices.boolean.no" => false,
                ],
                'choice_options' => [
                    "common.choices.boolean.yes" => ['conditional_form_name' => 'hazardousGoodsCode'],
                ],
                'choice_value' => function(?bool $v) {
                    if ($v === null) {
                        return null;
                    }

                    return $v ? 'yes' : 'no';
                },
                'mapped' => false,
                'constraints' => new NotNull(['message' => 'common.hazardous-goods.not-null', 'groups' => 'is-hazardous-goods']),
            ])
            ->add('hazardousGoodsCode', Gds\ChoiceType::class, [
                'choices' => $choices,
                'choice_options' => $choiceOptions,
                'label' => "{$translationKeyPrefix}.hazardous-goods-code.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.hazardous-goods-code.help",
                'constraints' => new NotNull(['message' => 'common.hazardous-goods.type-not-null', 'groups' => 'hazardous-goods-code']),
            ])
            ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
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

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        if (!$viewData instanceof HazardousGoodsInterface) {
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

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        if (!$viewData instanceof HazardousGoodsInterface) {
            return;
        }

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
