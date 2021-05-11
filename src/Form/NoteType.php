<?php

namespace App\Form;

use App\Entity\Address;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class NoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('note', Gds\TextareaType::class, [
                'label' => false,
                'constraints' => [new NotBlank()],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'type' => 'submit',
                'label' => 'Add note',
            ])
        ;
    }
}
