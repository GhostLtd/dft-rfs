<?php


namespace App\Form\Admin\DomesticSurvey;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class ImportDvlaFileUploadType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('file', Gds\FileUploadType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
                'label' => 'DVLA file',
                'constraints' => [
                    new File(),
                    new NotNull(['message' => 'Select a DVLA file to import']),
                ],
            ])
            ->add('survey_options', Gds\FieldsetType::class, [
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => 'Use these options if you want to override the automatic detection (based on the filename). The options apply to all surveys created during this import.',
            ])
            ->add('submit', Gds\ButtonType::class, ['type' => 'submit'])
            ;
        $builder->get('survey_options')
            ->add('isNorthernIreland', Gds\ChoiceType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
                'label' => 'Surveys region',
                'attr' => ['class' => 'govuk-radios--inline'],
                'choices' => [
                    'GB' => false,
                    'NI' => true,
                ],
            ])
            ->add('surveyPeriodStart', Gds\DateType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ;
    }
}