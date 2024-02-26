<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType as ExtendedCheckboxType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CheckboxType extends AbstractType
{
    #[\Override]
    public function getParent(): string
    {
        return ExtendedCheckboxType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'gds_checkbox';
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('small', false);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['small'] = $options['small'];
    }
}
