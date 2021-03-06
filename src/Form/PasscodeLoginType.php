<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\FieldsetType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PasscodeLoginType extends AbstractType
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
            ->add('passcode', FieldsetType::class, [
                'label' => 'passcode.login.label',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => 'passcode.login.help',
                'attr' => ['class' => 'govuk-fieldset__passcode'],
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

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'csrf_token_id' => 'authenticate.passcode',
        ]);
        if ($this->kernel->getEnvironment() !== 'dev') $resolver->setDefault('attr', ['autocomplete' => 'off']);
    }
}
