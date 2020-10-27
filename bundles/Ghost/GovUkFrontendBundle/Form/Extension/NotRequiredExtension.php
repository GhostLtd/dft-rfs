<?php


namespace Ghost\GovUkFrontendBundle\Form\Extension;


use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Ghost\GovUkFrontendBundle\Form\Type\TextareaType;
use Ghost\GovUkFrontendBundle\Form\Type\InputType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotRequiredExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes()
    {
        return [
            InputType::class,
            TextareaType::class,
            ChoiceType::class,
        ];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'required' => false,
        ]);
    }
}