<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasscodeLoginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('passcode', FieldsetType::class, [
                'label' => 'passcode.login.label',
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => 'passcode.login.help',
                'attr' => ['class' => 'govuk-fieldset__passcode'],
            ])
            ->add('login', ButtonType::class, ['type' => 'submit'])
        ;


        $builder->get('passcode')
            ->add('0', InputType::class, [
                'label' => 'passcode.login.passcode.0.label',
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
            ->add('1', InputType::class, [
                'label' => 'passcode.login.passcode.1.label',
                'attr' => ['class' => 'govuk-input--width-10'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'authenticate.passcode',
        ]);
    }
}
