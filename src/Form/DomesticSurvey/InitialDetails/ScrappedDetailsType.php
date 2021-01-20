<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\Domestic\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class ScrappedDetailsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('scrappedDate', Gds\DateType::class, [
                'label' => 'domestic.survey-response.scrapped-details.date.label',
                'label_attr' => ['class' => ($options['is_child_form'] ? 'govuk-fieldset__legend--s' : 'govuk-fieldset__legend--xl')],
                'constraints' => [
                    new NotNull([
                        'message' => "common.date.not-null",
                        'groups' => ['admin_scrapped'],
                    ])
                ],
                'property_path' => $options['date_property_path'],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => 'scrapped_details',
            'is_child_form' => false,
            'date_property_path' => 'unableToCompleteDate',
        ]);
    }
}
