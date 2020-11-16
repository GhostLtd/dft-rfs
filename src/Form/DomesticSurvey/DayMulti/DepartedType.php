<?php

namespace App\Form\DomesticSurvey\DayMulti;

use App\Entity\DomesticStopMultiple;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class DepartedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startLocation', Gds\InputType::class, [
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
            ->add('goodsLoaded', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'label_attr' => ['class' => 'govuk-label--m'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DomesticStopMultiple::class,
        ]);
    }
}
