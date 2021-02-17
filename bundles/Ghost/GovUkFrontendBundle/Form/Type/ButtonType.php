<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\SubmitType as ExtendedButtonType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ButtonType extends ExtendedButtonType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'prevent_double_click' => true,
            'type' => null,
            'label_html' => false,
        ]);

        $resolver->setAllowedValues('type', [null, 'button', 'submit']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['type'] = $options['type'];

        if ($view->vars['type'] === 'submit')
        {
            $view->vars['clicked'] = $form->isClicked();
        }

        if ($options['prevent_double_click']) {
            $view->vars['attr']['data-prevent-double-click'] = "true";
        }

        if ($options['disabled'] ?? false) {
            $view->vars['attr']['class'] = trim(($view->vars['attr']['class'] ?? "") . ' govuk-button--disabled');
        }

        $view->vars['label_html'] = $options['label_html'];
    }

    public function getParent()
    {
        return ExtendedButtonType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_button';
    }
}
