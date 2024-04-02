<?php

namespace Ghost\GovUkFrontendBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class FileUploadType extends FileType
{
    #[\Override]
    public function getParent(): ?string
    {
        return InputType::class;
    }

    #[\Override]
    public function getBlockPrefix(): string
    {
        return 'gds_file_upload';
    }

    #[\Override]
    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        parent::buildView($view, $form, $options);

        $view->vars['attr'] = array_merge([
        ], $view->vars['attr']);

        $view->vars['type'] = 'file';
        $view->vars['blockClass'] = 'govuk-file-upload';

        // The built-in symfony FileType clears the `value` var, but we need it to pass the GDS fixture tests
        $view->vars['value'] = $form->getData();
    }
}
