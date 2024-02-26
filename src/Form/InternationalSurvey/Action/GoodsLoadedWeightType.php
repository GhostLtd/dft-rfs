<?php

namespace App\Form\InternationalSurvey\Action;

use App\Entity\International\Action;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class GoodsLoadedWeightType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "international.action.goods-weight-loaded";
        $builder
            ->add('weightOfGoods', Gds\NumberType::class, [
                'label' => "{$translationKeyPrefix}.weight-of-goods.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => "{$translationKeyPrefix}.weight-of-goods.help",
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Action::class,
            'validation_groups' => 'action-loaded-weight',
        ]);
    }
}
