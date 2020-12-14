<?php

namespace App\Form;

use App\Entity\AbstractGoodsDescription;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractGoodsDescriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "{$options['translation_entity_key']}.goods-description";
        $builder
            ->add('goodsDescriptionFieldset', Gds\FieldsetType::class, [
                'label' => "{$translationKeyPrefix}.goods-description.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => "{$translationKeyPrefix}.goods-description.help",
                'help_html' => true,
            ])
        ;

        $goodsChoiceOptions = AbstractGoodsDescription::GOODS_DESCRIPTION_CHOICES;
        foreach ($goodsChoiceOptions as $k=>$v) {
            $goodsChoiceOptions[$k] = [
                'help' => "goods.description.help.{$v}",
            ];
        }
        $goodsChoiceOptions[AbstractGoodsDescription::GOODS_DESCRIPTION_TRANSLATION_PREFIX . AbstractGoodsDescription::GOODS_DESCRIPTION_OTHER]['conditional_form_name'] = 'goodsDescriptionOther';

        $choices = AbstractGoodsDescription::GOODS_DESCRIPTION_CHOICES;
        if ($options['is_summary_day']) {
            unset($choices[array_search(AbstractGoodsDescription::GOODS_DESCRIPTION_EMPTY, $choices)]);
        }

        $builder->get('goodsDescriptionFieldset')
            ->add('goodsDescription', Gds\ChoiceType::class, [
                'choices' => $choices,
                'choice_options' => $goodsChoiceOptions,
                'label' => false,
            ])
            ->add('goodsDescriptionOther', Gds\InputType::class, [
                'label' => false,
                'help' => "goods.description.help.other",
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired("translation_entity_key");
        $resolver->setAllowedValues("translation_entity_key", ['domestic.day-summary', 'domestic.day-stop', 'international.consignment']);

        $resolver->setDefaults([
            'is_summary_day' => false,
            'validation_groups' => 'goods-description',
        ]);
    }
}
