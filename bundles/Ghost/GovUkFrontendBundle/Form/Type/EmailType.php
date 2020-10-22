<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use App\Form\Gds\DataTransformer\IntegerToStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailType extends TextType
{
    public function getParent()
    {
        return TextType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_email';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['attr'] = array_merge([
            'class' => 'govuk-!-width-one-third',
            'inputmode' => 'email',
//            'pattern' => '[0-9]*',
        ], $view->vars['attr']);
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

//        $builder->addModelTransformer(new IntegerToStringTransformer($options['transformer_invalid_message']));
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
//            'transformer_invalid_message' => 'Enter a real number',
        ]);
    }
}
