<?php

namespace App\Form\Admin\DomesticSurvey\SurveyListField;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class WeekYearFilterType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $weeks = range(1, 53);
        $thisYear = intval((new \DateTime())->format('Y')) + 1;
        $years = range($thisYear, 2020);
        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($thisYear) {
                $data = $event->getData();
                if ($data && $data['week'] && !$data['year']) {
                    $data['year'] = $thisYear;
                    $event->setData($data);
                }
            })
            ->add('year', Gds\ChoiceType::class, [
                'choices' => array_combine($years, $years),
                'choice_translation_domain' => false,
                'expanded' => false,
                'placeholder' => 'Year',
                'label' => false,
            ])
            ->add('week', Gds\ChoiceType::class, [
                'choices' => array_combine($weeks, $weeks),
                'choice_translation_domain' => false,
                'expanded' => false,
                'placeholder' => 'Week',
                'label' => false,
            ])
            ;
    }
}
