<?php


namespace Ghost\GovUkFrontendBundle\Form\Extension;


use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FormTypeExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes()
    {
        return [
            FormType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefined(['label_html']);
        $resolver->setDefaults([
            'label_html' => false,
            'attr' => [
                'novalidate' => 'novalidate',
            ],
        ]);
        $resolver->setAllowedTypes('label_html', ['bool']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_html'] = $options['label_html'];
    }
}