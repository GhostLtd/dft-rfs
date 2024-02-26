<?php

namespace App\Form\Admin\RoRo\SurveyListField;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class MonthYearFilterType extends AbstractType
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $thisYear = intval((new \DateTime())->format('Y'));
        $years = range($thisYear, 2020);

        $months = [];
        for($monthNum = 1; $monthNum<=12; $monthNum++) {
            $monthDate = \DateTime::createFromFormat('!m', $monthNum);
            $months[$monthDate->format('F')] = $monthNum;
        }

        $builder
            ->addEventListener(FormEvents::PRE_SUBMIT, function(FormEvent $event) use ($thisYear) {
                $data = $event->getData();
                if ($data && $data['month'] && !$data['year']) {
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
            ->add('month', Gds\ChoiceType::class, [
                'choices' => $months,
                'choice_translation_domain' => false,
                'expanded' => false,
                'placeholder' => 'Month',
                'label' => false,
            ]);
    }
}
