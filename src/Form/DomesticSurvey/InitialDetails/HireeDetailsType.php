<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Form\AddressType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class HireeDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('hireeName', Gds\InputType::class, [
                'label' => 'domestic.survey-response.hiree-details.name.label',
                'help' => 'domestic.survey-response.hiree-details.name.help',
                'attr' => ['class' => 'govuk-input--width-20'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('hireeTelephone', Gds\InputType::class, [
                'label' => 'domestic.survey-response.hiree-details.telephone.label',
                'help' => 'domestic.survey-response.hiree-details.telephone.help',
                'attr' => ['class' => 'govuk-input--width-20'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('hireeEmail', Gds\EmailType::class, [
                'label' => 'domestic.survey-response.hiree-details.email.label',
                'help' => 'domestic.survey-response.hiree-details.email.help',
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('hireeAddress', AddressType::class, [
                'label' => 'domestic.survey-response.hiree-details.address.label',
                'help' => 'domestic.survey-response.hiree-details.address.help',
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => ['hiree_details', 'address'],
        ]);
    }
}
