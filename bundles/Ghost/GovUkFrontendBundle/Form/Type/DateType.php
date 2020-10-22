<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateType as ExtendedDateType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DateType extends ExtendedDateType
{
    public function getBlockPrefix()
    {
        return 'gds_date';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('label_is_page_heading', false);
        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);

        $resolver->setDefault('widget', 'text');
        $resolver->setAllowedValues('widget', ['text']);

        $resolver->setDefault('format', 'dd-MM-yyyy');
    }
}
