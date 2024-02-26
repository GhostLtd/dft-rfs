<?php

namespace Ghost\GovUkFrontendBundle\Form\Extension;

use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConditionalTypeExtension extends AbstractTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [
            RadioType::class,
            CheckboxType::class,
        ];
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'conditional_form_name' => null,
            'conditional_hide_form_names' => null,
        ]);

        $resolver->setAllowedTypes('conditional_form_name', ['null', 'string']);
        $resolver->setAllowedTypes('conditional_hide_form_names', ['null', 'string[]']);
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['conditional_form_name'] = $options['conditional_form_name'] ?? false;
        $view->vars['conditional_hide_form_names'] = $options['conditional_hide_form_names'] ?? false;
    }
}