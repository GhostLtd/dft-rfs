<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Ghost\GovUkFrontendBundle\Form\DataTransformer\DecimalToStringTransformer;
use Ghost\GovUkFrontendBundle\Form\DataTransformer\IntegerToStringTransformer;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FileUploadType extends FileType
{
    public function getParent()
    {
        return InputType::class;
    }

    public function getBlockPrefix()
    {
        return 'gds_file_upload';
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);

        $view->vars['attr'] = array_merge([
        ], $view->vars['attr']);

        $view->vars['type'] = 'file';
        $view->vars['blockClass'] = 'govuk-file-upload';

        // The built in symfony FileType clears the `value` var, but we need it to pass the GDS fixture tests
        $view->vars['value'] = $form->getData();
    }
}
