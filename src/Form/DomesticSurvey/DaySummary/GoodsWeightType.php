<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DaySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsWeightType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.day-summary.goods-weight";
        $builder
            ->add('weightOfGoodsLoaded', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods-loaded.label",
                'help' => "{$translationKeyPrefix}.weight-of-goods-loaded.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'suffix' => 'kg',
            ])
            ->add('weightOfGoodsUnloaded', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods-unloaded.label",
                'help' => "{$translationKeyPrefix}.weight-of-goods-unloaded.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'suffix' => 'kg',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
            'validation_groups' => 'day-summary.goods-weight',
        ]);
    }
}
