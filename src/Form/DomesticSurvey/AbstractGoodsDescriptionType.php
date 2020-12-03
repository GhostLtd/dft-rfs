<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\StopTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractGoodsDescriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.{$options['translation_entity_key']}.goods-description";
        $builder
            ->add('goodsDescriptionFieldset', Gds\FieldsetType::class, [
                'label' => "{$translationKeyPrefix}.goods-description.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => "{$translationKeyPrefix}.goods-description.help",
                'help_html' => true,
                'attr' => ['class' => 'govuk-input--5'],
            ])
        ;

        $goodsChoiceOptions = Day::GOODS_DESCRIPTION_CHOICES;
        foreach ($goodsChoiceOptions as $k=>$v) {
            $goodsChoiceOptions[$k] = [
                'help' => "domestic.goods-description.help.{$v}",
            ];
        }
        $goodsChoiceOptions[Day::GOODS_DESCRIPTION_TRANSLATION_PREFIX . Day::GOODS_DESCRIPTION_OTHER]['conditional_form_name'] = 'goodsDescriptionOther';

        $choices = Day::GOODS_DESCRIPTION_CHOICES;
        if ($options['is_summary_day']) {
            unset($choices[array_search(Day::GOODS_DESCRIPTION_EMPTY, $choices)]);
        }

        $builder->get('goodsDescriptionFieldset')
            ->add('goodsDescription', Gds\ChoiceType::class, [
                'choices' => $choices,
                'choice_options' => $goodsChoiceOptions,
                'label' => false,
            ])
            ->add('goodsDescriptionOther', Gds\InputType::class, [
                'label' => false,
                'help' => "domestic.goods-description.help.other",
            ])
            ;
    }

    use StopTypeTrait {
        StopTypeTrait::configureOptions as traitConfigureOptions;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $this->traitConfigureOptions($resolver);
        $resolver->setDefaults([
            'is_summary_day' => false,
            'validation_groups' => 'goods-description',
        ]);
    }
}
