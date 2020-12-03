<?php

namespace App\Form\DomesticSurvey\DayStop;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
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
//                'label_attr' => ['class' => 'govuk-label--xl'],
//                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--s'],
                'suffix' => 'kg',
            ])
            ->add('wasLimitedBy', Gds\ChoiceType::class, [
                'multiple' => true,
                'label' => "{$translationKeyPrefix}.was-limited-by.label",
                'help' => "{$translationKeyPrefix}.was-limited-by.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => [
                    'At capacity by weight' => 'weight',
                    'At capacity by space' => 'space',
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DayStop::class,
        ]);
    }
}