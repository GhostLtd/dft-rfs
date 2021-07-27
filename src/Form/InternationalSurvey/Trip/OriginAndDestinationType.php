<?php


namespace App\Form\InternationalSurvey\Trip;


use App\Entity\International\Trip;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class OriginAndDestinationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $translationKeyPrefix = 'international.trip.places';

        $builder
            ->add('origin', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.origin.label",
                'help' => "{$translationKeyPrefix}.origin.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ->add('destination', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.destination.label",
                'help' => "{$translationKeyPrefix}.destination.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-20'],
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Trip::class,
            'validation_groups' => ['trip_places'],
        ]);
    }
}