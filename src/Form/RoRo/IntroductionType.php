<?php

namespace App\Form\RoRo;

use App\Entity\RoRo\Survey;
use Ghost\GovUkFrontendBundle\Form\Type\BooleanChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntroductionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('isActiveForPeriod', BooleanChoiceType::class, [
            'label_attr' => ['class' => $options['for_admin'] ? 'govuk-label--m' : 'govuk-label--l'],
            'label' => 'roro.survey.introduction.is-active',
            'label_is_page_heading' => !$options['for_admin'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'validation_groups' => ['roro.is-active'],
            'for_admin' => false,
        ]);
    }
}