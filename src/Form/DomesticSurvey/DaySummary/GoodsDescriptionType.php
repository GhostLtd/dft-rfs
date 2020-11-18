<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsDescriptionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('goodsDescriptionFieldset', Gds\FieldsetType::class, [
                'label' => 'survey.domestic.forms.day-summary.goods-description.label',
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => 'survey.domestic.forms.day-summary.goods-description.help',
                'help_html' => true,
                'attr' => ['class' => 'govuk-input--5'],
            ])
        ;

        $goodsChoiceOptions = Day::GOODS_DESCRIPTION_CHOICES;
        foreach ($goodsChoiceOptions as $k=>$v) {
            $goodsChoiceOptions[$k] = [
                'help' => "survey.domestic.forms.day-summary.goods-description.{$v}.help",
            ];
        }
        $goodsChoiceOptions[Day::GOODS_DESCRIPTION_TRANSLATION_PREFIX . Day::GOODS_DESCRIPTION_OTHER]['conditional_form_name'] = 'goodsDescriptionOther';

        $builder->get('goodsDescriptionFieldset')
            ->add('goodsDescription', Gds\ChoiceType::class, [
                'choices' => Day::GOODS_DESCRIPTION_CHOICES,
                'choice_options' => $goodsChoiceOptions,
                'label' => false,
            ])
            ->add('goodsDescriptionOther', Gds\InputType::class, [
                'label' => false,
                'help' => 'survey.domestic.forms.day-summary.goods-description-other.help',
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
        ]);
    }
}
