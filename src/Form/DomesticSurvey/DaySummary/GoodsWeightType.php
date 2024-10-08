<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\DaySummary;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsWeightType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.day-summary.goods-weight";
        $builder
            ->add('weightOfGoodsLoaded', Gds\IntegerType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods-loaded.label",
                'help' => "{$translationKeyPrefix}.weight-of-goods-loaded.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
            ->add('weightOfGoodsUnloaded', Gds\IntegerType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods-unloaded.label",
                'help' => "{$translationKeyPrefix}.weight-of-goods-unloaded.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
            'validation_groups' => 'day-summary.goods-weight',
        ]);
    }
}
