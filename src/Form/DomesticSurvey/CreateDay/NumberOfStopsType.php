<?php

namespace App\Form\DomesticSurvey\CreateDay;

use App\Entity\Domestic\Day;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Ghost\GovUkFrontendBundle\Form\Type\ChoiceType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotNull;

class NumberOfStopsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) {
                $translationKeyPrefix = "domestic.day.number-of-stops";
                $event->getForm()
                    ->add('hasMoreThanFiveStops', ChoiceType::class, [
                        'choices' => [
                            "{$translationKeyPrefix}.number-of-stops.fewer-than-5" => false,
                            "{$translationKeyPrefix}.number-of-stops.5-or-more" => true,
                        ],
                        'label' => "{$translationKeyPrefix}.number-of-stops.label",
                        'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                        'label_is_page_heading' => true,
                        'label_translation_parameters' => ['dayNumber' => $event->getData()->getNumber()],
                        'help_html' => true,
                        'help' => "{$translationKeyPrefix}.number-of-stops.help",
                        'constraints' => new NotNull(['message' => 'common.choice.not-null'])
                    ])
                    ->add('continue', ButtonType::class, [
                        'type' => 'submit',
                    ])
                    ->add('cancel', ButtonType::class, [
                        'type' => 'submit',
                        'attr' => ['class' => 'govuk-button--secondary'],
                    ])
                    ;
            });
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Day::class,
        ]);
    }
}
