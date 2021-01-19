<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\Domestic\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScrappedDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('scrappedDate', Gds\DateType::class, [
                'label' => 'domestic.survey-response.scrapped-details.date.label',
                'label_attr' => ['class' => ($options['is_child_form'] ? 'govuk-fieldset__legend--s' : 'govuk-fieldset__legend--xl')],
                'help' => 'domestic.survey-response.scrapped-details.date.help',
                'property_path' => $options['date_property_path'],
                'constraints' => $options['date_constraints'],
                'mapped' => $options['date_mapped'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => 'scrapped_details',
            'is_child_form' => false,
            'date_constraints' => null,
            'date_property_path' => 'unableToCompleteDate',
            'date_mapped' => true,
        ]);
    }
}
