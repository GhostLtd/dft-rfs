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
    #[\Override]
    public function getParent(): ?string
    {
        return InputType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'gds_number';
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['attr'] = array_merge([
//            'class' => 'govuk-input--width-3',
            'inputmode' => $options['is_decimal'] ? 'decimal' : 'numeric',
            'pattern' => $options['is_decimal'] ? '[0-9.,]*' : '[0-9]*',
        ], $view->vars['attr']);

        if (!$options['is_decimal']) $view->vars['type'] = 'number';
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);

        $builder->addModelTransformer(
            $options['is_decimal'] ?
                new DecimalToStringTransformer($options['transformer_invalid_message']) :
                new IntegerToStringTransformer($options['transformer_invalid_message'])
        );
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'transformer_invalid_message' => 'common.number.invalid',
            'is_decimal' => false,
        ]);

        $resolver->setAllowedTypes('is_decimal', ['bool']);
    }
}
