<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\DecimalToStringTransformer;
use Ghost\GovUkFrontendBundle\Form\DataTransformer\IntegerToStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberType extends InputType
{
    public function getParent()
    {
        return InputType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_number';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['attr'] = array_merge([
//            'class' => 'govuk-input--width-3',
            'inputmode' => $options['is_decimal'] ? 'decimal' : 'numeric',
            'pattern' => $options['is_decimal'] ? '[0-9.,]*' : '[0-9]*',
        ], $view->vars['attr']);

        if (!$options['is_decimal']) $view->vars['type'] = 'number';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addModelTransformer(
            $options['is_decimal'] ?
                new DecimalToStringTransformer($options['transformer_invalid_message']) :
                new IntegerToStringTransformer($options['transformer_invalid_message'])
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'transformer_invalid_message' => 'Enter a real number',
            'is_decimal' => false,
        ]);

        $resolver->setAllowedTypes('is_decimal', ['bool']);
    }
}
