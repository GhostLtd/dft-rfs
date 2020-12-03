<?php

namespace App\Form;

use App\Entity\HazardousGoods;
use App\Entity\HazardousGoodsTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractHazardousGoodsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceOptions = HazardousGoods::CHOICES;
        foreach ($choiceOptions as $k=>$v) {
            $choiceOptions[$k] = [
                'label_html' => true,
            ];
        }

        $translationKeyPrefix = "{$options['translation_entity_key']}.hazardous-goods";
        $builder
            ->add('hazardousGoodsCode', Gds\ChoiceType::class, [
                'expanded' => true,
                'choices' => HazardousGoods::CHOICES,
                'choice_options' => $choiceOptions,
                'label' => "{$translationKeyPrefix}.hazardous-goods.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--xl'],
                'help' => "{$translationKeyPrefix}.hazardous-goods.help",
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => HazardousGoodsTrait::class,
            'validation_groups' => 'hazardous-goods',
        ]);
        $resolver->setRequired(["translation_entity_key"]);
        $resolver->setAllowedValues("translation_entity_key", ['domestic.day-summary', 'domestic.day-stop', 'international.[change me]']);
    }
}
