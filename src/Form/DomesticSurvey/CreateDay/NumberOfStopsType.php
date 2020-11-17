<?php

namespace App\Form\DomesticSurvey\CreateDay;

use App\Entity\Domestic\Day;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NumberOfStopsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('hasMoreThanFiveStops', ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
            ])
            ->add('continue', ButtonType::class, [
                'type' => 'submit',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Day::class,
        ]);
    }
}
