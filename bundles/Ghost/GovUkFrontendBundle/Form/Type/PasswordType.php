<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PasswordType extends InputType
{
    #[\Override]
    public function getParent(): ?string
    {
        return InputType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'gds_password';
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['type'] = 'password';
    }
}
