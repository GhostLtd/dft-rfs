<?php

namespace App\Form\DomesticSurvey\InitialDetails;

use App\Entity\Domestic\SurveyResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ScrappedDetailsType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('scrappedDate', Gds\DateType::class, [
                'label' => 'domestic.survey-response.scrapped-details.date.label',
                'help' => 'domestic.survey-response.scrapped-details.date.help',
                'label_attr' => ['class' => ($options['is_child_form'] ? 'govuk-fieldset__legend--s' : 'govuk-fieldset__legend--xl')],
                'property_path' => $options['date_property_path'],
            ])
        ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SurveyResponse::class,
            'validation_groups' => 'scrapped_details',
            'is_child_form' => false,
            'date_property_path' => 'unableToCompleteDate',
        ]);
    }
}
