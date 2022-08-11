<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PasswordType extends InputType
{
    public function getParent(): ?string
    {
        return InputType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gds_password';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['type'] = 'password';
    }
}
