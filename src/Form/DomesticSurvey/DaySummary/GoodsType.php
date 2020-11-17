<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\StopTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('goodsDescription', Gds\InputType::class, [
                'label' => 'survey.domestic.forms.day-summary.goods-description.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => 'survey.domestic.forms.day-summary.goods-description.help',
                'help_html' => true,
            ])
            ->add('weightOfGoodsLoaded', Gds\NumberType::class, [
                'label' => 'survey.domestic.forms.day-summary.weight-of-goods-loaded.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'suffix' => 'kg'
            ])
            ->add('weightOfGoodsUnloaded', Gds\NumberType::class, [
                'label' => 'survey.domestic.forms.day-summary.weight-of-goods-unloaded.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'suffix' => 'kg'
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
