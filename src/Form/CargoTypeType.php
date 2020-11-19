<?php

namespace App\Form;

use App\Entity\CargoType;
use App\Entity\CargoTypeTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class CargoTypeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceOptions = CargoType::CHOICES;
        foreach ($choiceOptions as $k=>$v) {
            $choiceOptions[$k] = [
            ];
        }

        $choices = CargoType::CHOICES;
        if ($options['is_summary_day']) {
            unset($choices[array_search(CargoType::CODE_NS_EMPTY, $choices)]);
        }

        $builder
            ->add('cargoTypeCode', Gds\ChoiceType::class, [
                'choices' => $choices,
                'choice_options' => $choiceOptions,
                'label' => 'survey.domestic.forms.day-summary.cargo-type.label',
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--xl'],
                'help' => 'survey.domestic.forms.day-summary.cargo-type.help',
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CargoTypeTrait::class,
            'is_summary_day' => false,
        ]);
    }
}
