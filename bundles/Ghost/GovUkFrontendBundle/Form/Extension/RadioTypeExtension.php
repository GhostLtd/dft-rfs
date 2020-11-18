<?php


namespace Ghost\GovUkFrontendBundle\Form\Extension;


use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\RadioType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RadioTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes()
    {
        return [
            RadioType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'conditional_form_name' => null,
        ]);

        $resolver->setAllowedTypes('conditional_form_name', ['null', 'string']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['conditional_form_name'] = $options['conditional_form_name'] ?? false;
    }
}