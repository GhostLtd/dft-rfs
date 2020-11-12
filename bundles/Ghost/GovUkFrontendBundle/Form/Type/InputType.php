<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType as ExtendedTextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InputType extends ExtendedTextType
{
    public function getBlockPrefix()
    {
        return 'gds_input';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['prefix'] = $options['prefix'];
        $view->vars['suffix'] = $options['suffix'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
           'prefix' => false,
           'suffix' => false,
        ]);
    }
}
