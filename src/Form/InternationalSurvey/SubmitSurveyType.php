<?php

namespace App\Form\InternationalSurvey;

use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class SubmitSurveyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('submit', ButtonType::class, [
                'label' => 'Submit survey',
            ]);
    }
}