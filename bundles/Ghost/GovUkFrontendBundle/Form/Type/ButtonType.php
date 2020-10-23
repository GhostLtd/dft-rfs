<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ButtonType as ExtendedButtonType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ButtonType extends ExtendedButtonType
{
//    const STATE_ACTIVE = 'active';
//    const STATE_FOCUS = 'focus';
//    const STATE_HOVER = 'hover';

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'prevent_double_click' => false,
//            'state' => false,
        ]);

//        $resolver->setAllowedValues('state', [false, null, self::STATE_ACTIVE, self::STATE_FOCUS, self::STATE_HOVER]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        if ($options['prevent_double_click']) $view->vars['attr']['data-prevent-double-click'] = "true";
//        if ($options['state']) $view->vars['attr']['class'] = trim(($view->vars['attr']['class'] ?? "") . ":{$options['state']}");
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
