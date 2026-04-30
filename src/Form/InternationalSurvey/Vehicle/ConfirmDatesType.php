<?php

namespace App\Form\InternationalSurvey\Vehicle;

use App\Form\AbstractConfirmType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfirmDatesType extends AbstractConfirmType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        // Flip the parent's mapped=false so 'confirm' binds to the wizard state,
        // letting the workflow guards branch on it. (AddAnotherType inherits the
        // unmapped behaviour for the still-legacy Action wizard.)
        $builder->get('confirm')->setMapped(true);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.vehicle.confirm-dates',
            'null_message' => 'international.vehicle.confirm-dates.not-null',
        ]);
    }
}
