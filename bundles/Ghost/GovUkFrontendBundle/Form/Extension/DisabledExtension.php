<?php


namespace Ghost\GovUkFrontendBundle\Form\Extension;


use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\BaseType;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DisabledExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes()
    {
        return [
            ButtonType::class,
        ];
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        if ($options['disabled'] ?? false) {
            $view->vars['attr']['class'] = trim(($view->vars['attr']['class'] ?? "") . ' govuk-button--disabled');
        }
    }
}