<?php

namespace App\Form\Admin\DomesticSurvey;

use App\Entity\Domestic\Survey;
use Ghost\GovUkCoreBundle\Form\ConfirmActionType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfirmApprovedActionType extends ConfirmActionType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reasonForUnfilledSurvey', Gds\ChoiceType::class, [
                'choices' => Survey::UNFILLED_SURVEY_REASON_CHOICES,
                'choice_translation_domain' => 'messages',
                'label_attr' => ['class' => 'govuk-label--s'],
            ]);

        // Inherits button_group with confirm submit + cancel link from bundle's ConfirmActionType
        parent::buildForm($builder, $options);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'data_class' => Survey::class,
            'validation_groups' => ['admin_reason_for_unfilled_survey'],
        ]);
    }
}
