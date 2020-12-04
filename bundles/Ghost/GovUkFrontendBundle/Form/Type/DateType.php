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
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'widget' => 'text',
            'invalid_message' => 'common.date.invalid',
        ]);
        $resolver->setAllowedValues('widget', ['text']);

        $resolver->setDefault('format', 'dd-MM-yyyy');
    }
}
