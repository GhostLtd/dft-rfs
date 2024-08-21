<?php

namespace App\Form\Admin;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NoteType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('note', Gds\TextareaType::class, [
                'label' => false,
            ])
            ->add('wasChased', Gds\BooleanChoiceType::class, [
                'label' => 'Is this note about contact with the haulier?',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => 'Add note',
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('validation_groups', ['admin_comment']);
    }
}
