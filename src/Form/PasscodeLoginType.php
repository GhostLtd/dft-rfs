<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class PasscodeLoginType extends AbstractType
{
    public function __construct(protected string $appEnvironment, protected CsrfTokenManagerInterface $tokenManager)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('passcode', FieldsetType::class, [
                'label' => 'passcode.login.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'passcode.login.help',
            ])
            ->add('token', HiddenType::class, [
                'data' => $this->tokenManager->getToken($options['csrf_token_id'])
            ])
            ->add('sign_in', ButtonType::class, ['type' => 'submit'])
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

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'authenticate.passcode',
            'csrf_protection' => false, // We handle it manually... (see PasscodeAuthenticator)
        ]);

        if ($this->appEnvironment !== 'dev') {
            $resolver->setDefault('attr', ['autocomplete' => 'off']);
        }
    }
}
