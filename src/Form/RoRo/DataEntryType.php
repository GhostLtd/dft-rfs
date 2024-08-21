<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\Survey;
use App\Form\Admin\RoRo\DataMapper\DataEntryDataMapper;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use PHPUnit\Framework\Constraint\Callback;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class DataEntryType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper(new DataEntryDataMapper())
            ->add('data', TextareaType::class, [
                'attr' => ['class' => 'govuk-!-margin-bottom-5'],
                'label' => 'roro.survey.data-entry.data.label',
                'help' => 'roro.survey.data-entry.data.help',
                'label_attr' => ['class' => 'govuk-label--l'],
                'mapped' => false,
                'trim' => false,
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'error_mapping' => [
                '.' => 'data',
            ],
            'validation_groups' => [
                'roro.survey.data-entry'
            ]
        ]);
    }
}
