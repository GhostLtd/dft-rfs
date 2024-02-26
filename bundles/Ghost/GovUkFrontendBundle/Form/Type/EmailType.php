<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class EmailType extends InputType
{
    #[\Override]
    public function getParent(): ?string
    {
        return InputType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'gds_email';
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['attr'] = array_merge([
            'class' => 'govuk-input--width-20',
            'inputmode' => 'email',
        ], $view->vars['attr']);
    }
}
