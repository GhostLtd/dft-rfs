<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BooleanChoiceType extends ChoiceType
{
    public function getParent()
    {
        return ChoiceType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_boolean_choice';
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->addModelTransformer(new CallbackTransformer(
            function ($property) {
                return (string) $property;
            },
            function ($property) {
                return (bool) $property;
            }
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['attr'] = array_merge([
            'class' => 'govuk-radios--inline',
        ], $view->vars['attr']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'expanded' => true,
            'choices' => [
                'No' => false,
                'Yes' => true,
            ],
        ]);

        $resolver->addNormalizer('choices', function($resolver, $choices){
            foreach($choices as &$choice) {
                if ($choice === true) $choice = "1";
                if ($choice === false) $choice = "0";
            }
            return $choices;
        });
    }
}
