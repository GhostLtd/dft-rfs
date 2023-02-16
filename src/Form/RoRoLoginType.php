<?php

namespace App\Form;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class RoRoLoginType extends AbstractType
{
    protected string $appEnvironment;

    public function __construct(string $appEnvironment)
    {
        $this->appEnvironment = $appEnvironment;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username', InputType::class, [
                'label' => 'roro.login.username.label',
                'attr' => ['class' => 'govuk-input--width-10'],
                'constraints' => [
                    new NotBlank([
                        'message' => 'roro.login.username-blank',
                    ]),
                    new Email([
                        'message' => 'roro.login.invalid-email',
                    ]),
                ],
            ])
            ->add('sign_in', ButtonType::class, ['type' => 'submit'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        if ($this->appEnvironment !== 'dev') {
            $resolver->setDefault('attr', ['autocomplete' => 'off']);
        }
    }
}