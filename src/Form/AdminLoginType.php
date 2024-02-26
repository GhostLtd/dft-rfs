<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\PasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class AdminLoginType extends AbstractType
{
    public function __construct(protected string $appEnvironment, protected CsrfTokenManagerInterface $tokenManager)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('credentials', FieldsetType::class, [
                'label' => 'admin.login.label',
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--l'],
                'help' => 'admin.login.help',
                'attr' => ['class' => ''],
            ])
            ->add('token', HiddenType::class, [
                'data' => $this->tokenManager->getToken($options['csrf_token_id'])
            ])
            ->add('login', ButtonType::class, ['type' => 'submit'])
        ;

        $builder->get('credentials')
            ->add('username', InputType::class, [
                'label' => 'admin.login.username.label',
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('password', PasswordType::class, [
                'label' => 'admin.login.password.label',
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'authenticate.admin',
            'csrf_protection' => false, // We handle it manually... (see AdminFormAuthenticator)
        ]);

        if ($this->appEnvironment !== 'dev') {
            $resolver->setDefault('attr', ['autocomplete' => 'off']);
        }
    }
}
