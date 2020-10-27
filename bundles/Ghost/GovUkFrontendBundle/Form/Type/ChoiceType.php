<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\EventListener\ContextualOptionsFormListener;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType as ExtendedChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
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

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (is_array($options['choice_attr'])) {
            $options['choice_attr'] = function($value, $key, $index) use ($options) {
                return $options['choice_attr'][$key] ?? [];
            };
        }

        $choiceOptions = $options['choice_options'];
        $builder->addEventSubscriber(new ContextualOptionsFormListener(
            function (FormEvent $event, $name, array $options) use ($choiceOptions) {
                return array_merge($options, $choiceOptions[$options['label']] ?? []);
            }
        ));

        parent::buildForm($builder, $options);
    }

    protected function getChildByLabel(FormBuilderInterface $builder, $label)
    {
        foreach($builder->all() as $item)
        {
            /** @var FormBuilderInterface $item */
            if ($item->getOption('label') === $label)
            {
                return $item;
            }
        }
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];

//        $typeName = $options['expanded'] ? $options['multiple'] ? 'checkbox' : 'radio' : 'select';
//        $view->vars['blockPrefix'] = "gds_{$typeName}";
//
//        $class = "govuk-" . ($options['expanded'] ? $options['multiple'] ? 'checkboxes' : 'radios' : 'select');
//        $view->vars['attr']['class'] = trim("$class " . ($options['attr']['class'] ?? ''));
//        $view->vars['classPrefix'] = $class;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'placeholder' => false,
            'label_is_page_heading' => false,
            'choice_options' => null,
        ]);

        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);
        $resolver->setAllowedTypes('choice_attr', ['null', 'array', 'callable']);
        $resolver->setAllowedTypes('choice_options', ['null', 'array']);

        $resolver->setNormalizer('multiple', function (Options $options, $value) {
            if (true === $value && false === $options['expanded']) {
                throw new InvalidOptionsException('Option combination multiple=true, expanded=false is unsupported');
            }

            return $value;
        });
    }
}
