<?php

namespace App\Form\InternationalSurvey\Action;

use App\Form\AbstractConfirmType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddAnotherType extends AbstractConfirmType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        parent::buildForm($builder, $options);
        // Flip the parent's mapped=false so 'confirm' binds to the wizard state,
        // letting the workflow guards branch on it.
        $builder->get('confirm')->setMapped(true);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'translation_key_prefix' => 'international.action.add-another',
            'null_message' => 'international.action.add-another.not-null',
        ]);
    }
}
