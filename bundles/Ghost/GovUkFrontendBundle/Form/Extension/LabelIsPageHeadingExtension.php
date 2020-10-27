<?php


namespace Ghost\GovUkFrontendBundle\Form\Extension;


use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\TimeType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LabelIsPageHeadingExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes()
    {
        return [
            TextareaType::class,
            InputType::class,
            TimeType::class,
            FieldsetType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'label_is_page_heading' => false,
        ]);
        $resolver->setAllowedTypes('label_is_page_heading', ['bool']);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['label_is_page_heading'] = $options['label_is_page_heading'];
    }
}