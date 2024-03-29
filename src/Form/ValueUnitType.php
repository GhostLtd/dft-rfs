<?php

namespace App\Form;

use App\Entity\ValueUnitInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ValueUnitType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $defaultValueOptions = [
            'label' => false,
            'attr' => [
            ],
        ];
        $defaultUnitsOptions = [
            'label' => false,
            'attr' => [
                'class' => 'govuk-radios--inline',
            ],
        ];

        $builder
            ->add('value', $options['value_form_type'], array_merge($defaultValueOptions, $options['value_options']))
            ->add('unit', $options['unit_form_type'], array_merge($defaultUnitsOptions, $options['unit_options']))
        ;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
        $view->vars['fieldset_attr'] = ['id' => $view->vars['id']];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ValueUnitInterface::class,
            'value_form_type' => Gds\NumberType::class,
            'unit_form_type' => Gds\ChoiceType::class,
            'value_options' => [],
            'unit_options' => [],
            'label_is_page_heading' => false,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'gds_value_units';
    }
}
