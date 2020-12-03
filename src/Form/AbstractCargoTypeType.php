<?php

namespace App\Form;

use App\Entity\CargoType;
use App\Entity\CargoTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractCargoTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceOptions = CargoType::CHOICES;
        foreach ($choiceOptions as $k=>$v) {
            $choiceOptions[$k] = [
                'help' => "goods.cargo-type.help.{$v}",
            ];
        }

        $choices = CargoType::CHOICES;
        if ($options['is_summary_day']) {
            unset($choices[array_search(CargoType::CODE_NS_EMPTY, $choices)]);
        }

        $translationKeyPrefix = "{$options['translation_namespace_key']}.{$options['translation_entity_key']}.cargo-type";
        $builder
            ->add('cargoTypeCode', Gds\ChoiceType::class, [
                'choices' => $choices,
                'choice_options' => $choiceOptions,
                'label' => "{$translationKeyPrefix}.cargo-type-code.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--xl'],
                'help' => "{$translationKeyPrefix}.cargo-type-code.help",
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CargoTypeTrait::class,
            'is_summary_day' => false,
        ]);
        $resolver->setRequired(["translation_entity_key", "translation_namespace_key"]);
        $resolver->setAllowedValues("translation_entity_key", ['day-summary', 'day-stop']);
        $resolver->setAllowedValues("translation_namespace_key", ['domestic', 'international']);
    }
}
