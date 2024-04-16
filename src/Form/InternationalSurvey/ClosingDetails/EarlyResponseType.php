<?php

namespace App\Form\InternationalSurvey\ClosingDetails;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Validator\Constraints\NotNull;

class EarlyResponseType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('is_correct', Gds\BooleanChoiceType::class, [
                'mapped' => false,
                'choices' => [
                    'international.closing-details.early-response.is-correct.yes' => "true",
                    'international.closing-details.early-response.is-correct.no' => "false",
                ],
                'label' => "international.closing-details.early-response.is-correct.label",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                'help' => "international.closing-details.early-response.is-correct.help",
                'constraints' => new NotNull(['message' => 'international.closing-details.early-response.not-null']),
                'choice_translation_domain' => 'messages',
            ]);
    }
}
