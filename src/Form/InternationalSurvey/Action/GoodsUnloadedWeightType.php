<?php

namespace App\Form\InternationalSurvey\Action;

use App\Entity\International\Consignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsUnloadedWeightType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "international.action.goods-weight-unloaded";
        $builder
            ->add('weightOfGoods', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--xl'],
                'help' => "{$translationKeyPrefix}.weight-of-goods.help",
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Consignment::class,
            'validation_groups' => 'action-unloaded-weight',
        ]);
    }
}
