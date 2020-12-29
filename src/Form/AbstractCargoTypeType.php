<?php

namespace App\Form;

use App\Entity\CargoType;
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

        $translationKeyPrefix = "{$options['translation_entity_key']}.cargo-type";
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
            'is_summary_day' => false,
            'validation_groups' => 'cargo-type',
        ]);
        $resolver->setRequired(["translation_entity_key"]);
    }
}
