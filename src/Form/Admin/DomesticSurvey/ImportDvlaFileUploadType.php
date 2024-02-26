<?php

namespace App\Form\Admin\DomesticSurvey;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\NotNull;

class ImportDvlaFileUploadType extends AbstractType implements DataMapperInterface
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->setDataMapper($this);
        $builder
            ->add('file', Gds\FileUploadType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
                'label' => 'DVLA file',
                'constraints' => [
                    new File(),
                    new NotNull(['message' => 'Select a DVLA file to import']),
                ],
            ])
            ->add('override_defaults', Gds\ChoiceType::class, [
                'label' => 'Region and survey start date',
                'label_attr' => ['class' => 'govuk-label--s'],
                'choices' => ['Automatic (from filename)' => false, 'Override' => true],
                'choice_options' => [
                    'Override' => [
                        'conditional_form_name' => 'survey_options',
                    ],
                ],
//                'data' => false,
            ])
            ->add('survey_options', Gds\FieldsetType::class, [
                'label_attr' => ['class' => 'govuk-label--l'],
                'help' => 'Use these options <strong>ONLY</strong> if you want to override the automatic detection (based on the filename). The options apply to all surveys created during this import.',
                'help_html' => true,
                'error_bubbling' => true,
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
                'error_bubbling' => true,
            ])
            ->add('surveyPeriodStart', Gds\DateType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
                'error_bubbling' => true,
            ])
            ->add('allowHistoricalDate', Gds\CheckboxType::class, [
                'label_attr' => ['class' => 'govuk-label--s'],
                'label' => 'Allow survey period start date in the past',
                'help' => 'Check this box <strong>ONLY</strong> if you need to import a backdated import in extenuating circumstances',
                'help_html' => true,
            ]);
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        $forms = iterator_to_array($forms);
        $forms['file']->setData($viewData['file'] ?? null);
        $forms['override_defaults']->setData($viewData['override_defaults'] ?? false);
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        $viewData['file'] = $forms['file']->getData();
        if ($forms['override_defaults']->getData() === true) {
            $viewData['isNorthernIreland'] = $forms['isNorthernIreland']->getData();
            $viewData['surveyPeriodStart'] = $forms['surveyPeriodStart']->getData();
        }
    }
}
