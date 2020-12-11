<?php

namespace App\Form\DomesticSurvey\DayStop;

use App\Entity\Domestic\DayStop;
use App\Form\LimitedByType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsWeightType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "domestic.day-stop.goods-weight";
        $builder
            ->add('weightOfGoodsCarried', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods-carried.label",
                'help' => "{$translationKeyPrefix}.weight-of-goods-carried.help",
                'attr' => ['class' => 'govuk-input--width-5'],
                'label_attr' => ['class' => 'govuk-label--s'],
                'suffix' => 'kg',
            ])
            ->add('wasLimitedBy', LimitedByType::class, [
                'label' => "{$translationKeyPrefix}.was-limited-by.label",
                'help' => "{$translationKeyPrefix}.was-limited-by.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DayStop::class,
            'validation_groups' => 'day-stop.goods-weight'
        ]);
    }
}
