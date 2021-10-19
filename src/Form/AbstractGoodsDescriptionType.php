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
    public function buildForm(FormBuilderInterface $builder, array $options)
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired("translation_entity_key");
        $resolver->setDefaults([
            'is_summary_day' => false,
            'validation_groups' => 'goods-description',
        ]);
    }

    public function mapDataToForms($data, $forms)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$data instanceof GoodsDescriptionInterface) {
            throw new UnexpectedTypeException($data, GoodsDescriptionInterface::class);
        }

        if (!isset($forms['goodsDescription'])) {
            return;
        }

        $forms['goodsDescription']->setData($data->getGoodsDescription());
        $forms['goodsDescriptionOther']->setData($data->getGoodsDescriptionOther());
    }

    /**
     * @param FormInterface[]|Traversable $forms
     * @param GoodsDescriptionInterface $data
     */
    public function mapFormsToData($forms, &$data)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$data instanceof GoodsDescriptionInterface) {
            throw new UnexpectedTypeException($data, GoodsDescriptionInterface::class);
        }

        if (!isset($forms['goodsDescription'])) {
            return;
        }

        $goodsDescription = $forms['goodsDescription']->getData();
        $goodsDescriptionOther = $goodsDescription === AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER ?
            $forms['goodsDescriptionOther']->getData() : null;

        $data
            ->setGoodsDescription($goodsDescription)
            ->setGoodsDescriptionOther($goodsDescriptionOther);
    }
}
