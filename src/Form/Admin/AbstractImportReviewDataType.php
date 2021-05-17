<?php

namespace App\Form\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractImportReviewDataType extends AbstractType
{
    abstract protected function choiceLabel($data);

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($options) {
            $choices = array_combine(array_map([$this, 'choiceLabel'], $options['surveys']), array_keys($options['surveys']));

            $choiceOptions = $choices;
            foreach ($choiceOptions as $k=>$v) {
                $choiceOptions[$k] = [
                    'label_html' => true,
                ];
            }

            $safeFilename = htmlspecialchars(
                $options['uploaded_filename'],
                ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $event->getForm()
                ->add('review_data', Gds\ChoiceType::class, [
                    'data' => array_keys($options['surveys']),
                    'multiple' => true,
                    'help' => count($options['surveys']) . " records imported from <kbd>{$safeFilename}</kbd>",
                    'help_html' => true,
                    'label' => 'Data found in file',
                    'label_attr' => ['class' => 'govuk-label--m'],
                    'attr' => [
                        'class' => 'govuk-checkboxes--small govuk-bulk-import-review',
                    ],
                    'choice_options' => $choiceOptions,
                    'choices' => $choices,
                ])
            ;

            if ($choices) {
                $event->getForm()->add('submit', Gds\ButtonType::class, ['type' => 'submit']);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'surveys',
            'uploaded_filename',
        ]);

        $resolver->setDefaults([
            'attr' => [
                'data-prevent-double-click' => 'true',
            ],
        ]);
    }
}