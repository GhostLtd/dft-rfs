<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\Trip;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class OriginAndDestinationType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('origin', Gds\InputType::class, [
                'label' => "international.trip.places.origin.label",
                'help' => "international.trip.places.origin.help",
                'help_html' => 'markdown',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('destination', Gds\InputType::class, [
                'label' => "international.trip.places.destination.label",
                'help' => "international.trip.places.destination.help",
                'help_html' => 'markdown',
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'validation_groups' => ['trip_places'],
        ]);
    }
}
