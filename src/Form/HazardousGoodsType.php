<?php

namespace App\Form;

use App\Entity\HazardousGoods;
use App\Entity\HazardousGoodsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class HazardousGoodsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceOptions = HazardousGoods::CHOICES;
        foreach ($choiceOptions as $k=>$v) {
            $choiceOptions[$k] = [
                'label_html' => true,
            ];
        }

        $builder
            ->add('hazardousGoodsCode', Gds\ChoiceType::class, [
                'expanded' => true,
//                'placeholder' => 'survey.domestic.forms.day-summary.hazardous-goods.placeholder',
                'choices' => HazardousGoods::CHOICES,
                'choice_options' => $choiceOptions,
                'label' => 'survey.domestic.forms.day-summary.hazardous-goods.label',
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--xl'],
                'help' => 'survey.domestic.forms.day-summary.hazardous-goods.help',
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => HazardousGoodsTrait::class,
        ]);
    }
}
