<?php

namespace App\Form\Admin\DomesticSurvey\Edit;

use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\VehicleAndBusinessDetails\BusinessDetailsType as EmbeddedBusinessDetailsType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class BusinessDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('business_details', EmbeddedBusinessDetailsType::class, [
                'inherit_data' => true,
                'label' => false,
                'expanded_employees' => false,
                'constraints' => [new Valid()],
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
        ]);
    }
}