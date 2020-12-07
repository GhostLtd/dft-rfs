<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Ghost\GovUkFrontendBundle\Form\Type\PasswordType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminLoginType extends AbstractType
{
    /**
     * @var KernelInterface
     */
    private $kernel;

    public function __construct(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('credentials', FieldsetType::class, [
                'label' => 'admin.login.label',
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--l'],
                'help' => 'admin.login.help',
                'attr' => ['class' => ''],
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'authenticate.admin',
        ]);
        if ($this->kernel->getEnvironment() !== 'dev') $resolver->setDefault('attr', ['autocomplete' => 'off']);
    }
}
