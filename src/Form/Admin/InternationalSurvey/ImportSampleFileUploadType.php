<?php

namespace App\Form\Admin\InternationalSurvey;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class ImportSampleFileUploadType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('file', Gds\FileUploadType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
                'label' => 'Sample file',
                'constraints' => [
                    new File(),
                    new NotNull(['message' => 'Select a Sample file to import']),
                ],
            ])
            ->add('submit', Gds\ButtonType::class, ['type' => 'submit'])
            ;
    }
}
