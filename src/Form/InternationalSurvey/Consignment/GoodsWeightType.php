<?php

namespace App\Form\InternationalSurvey\Consignment;

use App\Entity\International\Consignment;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsWeightType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = "international.consignment.goods-weight";
        $builder
            ->add('weightOfGoodsCarried', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods-carried.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => "{$translationKeyPrefix}.weight-of-goods-carried.help",
                'attr' => ['class' => 'govuk-input--5'],
                'suffix' => 'kg',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Consignment::class,
            'validation_groups' => 'weight-of-goods-carried',
        ]);
    }
}
