<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\ChoiceType as ExtendedChoiceType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceType extends ExtendedChoiceType
{
    public function getParent()
    {
        return ExtendedChoiceType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_choice';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
        $view->vars['choice_help'] = $options['choice_help'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefault('label_is_page_heading', false);
        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);

        $resolver->setDefault('choice_help', null);
        $resolver->setAllowedTypes('choice_help', ['null', 'array']);

        $resolver->setNormalizer('multiple', function (Options $options, $value) {
            if (true === $value && false === $options['expanded']) {
                throw new InvalidOptionsException('Option combination multiple=true, expanded=false is unsupported');
            }

            return $value;
        });
    }
}
