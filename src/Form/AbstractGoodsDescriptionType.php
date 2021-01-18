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
}
