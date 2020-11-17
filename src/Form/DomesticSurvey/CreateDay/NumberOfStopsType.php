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

class NumberOfStopsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event){
                $event->getForm()
                    ->add('hasMoreThanFiveStops', ChoiceType::class, [
                        'choices' => [
                            'survey.domestic.forms.number-of-stops.fewer-than-5' => false,
                            'survey.domestic.forms.number-of-stops.5-or-more' => true,
                        ],
                        'label' => 'survey.domestic.forms.number-of-stops.heading',
                        'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                        'label_is_page_heading' => true,
                        'label_translation_parameters' => ['dayNumber' => $event->getData()->getNumber()],
                        'help_html' => true,
                        'help' => 'survey.domestic.forms.number-of-stops.help',
                    ])
                    ->add('continue', ButtonType::class, [
                        'type' => 'submit',
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
