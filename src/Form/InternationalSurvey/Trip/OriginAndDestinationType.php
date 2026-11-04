<?php

namespace App\Form\InternationalSurvey\Trip;

use App\Entity\International\Trip;
use App\Utility\PostcodeChangeoverHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class OriginAndDestinationType extends AbstractType
{
    public function __construct(private PostcodeChangeoverHelper $changeoverHelper)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            /** @var Trip|null $trip */
            $trip = $event->getData();
            $survey = $trip?->getVehicle()?->getSurveyResponse()?->getSurvey();
            $isPostcodeOnly = $this->changeoverHelper->isIrhsPostcodeOnly($survey);

            foreach (['origin', 'destination'] as $field) {
                $translationKeyPrefix = "international.trip.places.{$field}";

                $event->getForm()->add($field, Gds\InputType::class, array_merge([
                    'label_attr' => ['class' => 'govuk-label--s'],
                ], $isPostcodeOnly ? [
                    'label' => "{$translationKeyPrefix}.postcode-only.label",
                    'help' => "{$translationKeyPrefix}.postcode-only.help",
                    'attr' => ['class' => 'govuk-input--width-10'],
                    'constraints' => [PostcodeChangeoverHelper::createIrhsPostcodeConstraint(['trip_places'])],
                ] : [
                    'label' => "{$translationKeyPrefix}.label",
                    'help' => "{$translationKeyPrefix}.help",
                    'help_html' => 'markdown',
                    'attr' => ['class' => 'govuk-input--width-20'],
                ]));
            }
        });
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
