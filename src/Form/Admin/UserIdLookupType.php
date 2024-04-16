<?php

namespace App\Form\Admin;

use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserIdLookupType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('user_id', Gds\IntegerType::class, [
                'label' => 'User identifier',
                'label_attr' => [
                    'class' => 'govuk-label--s'
                ],
                'constraints' => [
                    new NotBlank(),
                ],
                'attr' => [
                    'class' => 'govuk-input--width-5',
                ],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => 'Lookup',
            ]);
    }
}
