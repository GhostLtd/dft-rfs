<?php

namespace App\Form;

use App\Entity\AbstractGoodsDescription;
use App\Entity\GoodsDescriptionInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Traversable;

abstract class AbstractGoodsDescriptionType extends AbstractType implements DataMapperInterface
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "{$options['translation_entity_key']}.goods-description";

        [$choices, $choiceOptions] = AbstractGoodsDescription::getFormChoicesAndOptions($options['is_summary_day']);

        $builder
            ->add('goodsDescription', Gds\ChoiceType::class, [
                'choices' => $choices,
                'choice_options' => $choiceOptions,
                'label' => "{$translationKeyPrefix}.goods-description.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => "{$translationKeyPrefix}.goods-description.help",
                'help_html' => true,
            ])
            ->add('goodsDescriptionOther', Gds\InputType::class, [
                'label' => false,
                'help' => "goods.description.help.other",
            ])
            ->setDataMapper($this)
            ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired("translation_entity_key");
        $resolver->setDefaults([
            'is_summary_day' => false,
            'validation_groups' => 'goods-description',
        ]);
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof GoodsDescriptionInterface) {
            throw new UnexpectedTypeException($viewData, GoodsDescriptionInterface::class);
        }

        if (!isset($forms['goodsDescription'])) {
            return;
        }

        $forms['goodsDescription']->setData($viewData->getGoodsDescription());
        $forms['goodsDescriptionOther']->setData($viewData->getGoodsDescriptionOther());
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof GoodsDescriptionInterface) {
            throw new UnexpectedTypeException($viewData, GoodsDescriptionInterface::class);
        }

        if (!isset($forms['goodsDescription'])) {
            return;
        }

        $goodsDescription = $forms['goodsDescription']->getData();
        $goodsDescriptionOther = $goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER ?
            $forms['goodsDescriptionOther']->getData() : null;

        $viewData
            ->setGoodsDescription($goodsDescription)
            ->setGoodsDescriptionOther($goodsDescriptionOther);
    }
}
