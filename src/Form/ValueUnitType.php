<?php

namespace App\Form;

use App\Entity\ValueUnitInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValueUnitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultValueOptions = [
            'label' => false,
        ];
        $defaultUnitsOptions = [
            'label' => false,
        ];

        $builder
            ->add('value', $options['value_form_type'], array_merge($defaultValueOptions, $options['value_options']))
            ->add('units', $options['units_form_type'], array_merge($defaultUnitsOptions, $options['units_options']))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ValueUnitInterface::class,
            'value_form_type' => Gds\NumberType::class,
            'units_form_type' => Gds\ChoiceType::class,
            'value_options' => [],
            'units_options' => [],
        ]);
    }
}
