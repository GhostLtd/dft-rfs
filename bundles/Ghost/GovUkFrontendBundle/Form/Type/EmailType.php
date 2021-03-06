<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use App\Form\Gds\DataTransformer\IntegerToStringTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailType extends InputType
{
    public function getParent()
    {
        return InputType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_email';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['attr'] = array_merge([
            'class' => 'govuk-input--width-20',
            'inputmode' => 'email',
        ], $view->vars['attr']);
    }
}
