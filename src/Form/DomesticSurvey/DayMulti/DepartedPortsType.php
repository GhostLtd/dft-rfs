<?php

namespace App\Form\DomesticSurvey\DayMulti;

use App\Entity\DomesticStopDay;
use App\Entity\DomesticStopMultiple;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class DepartedPortsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('transferredFrom', Gds\ChoiceType::class, [
                'choices' => DomesticStopDay::TRANSFER_CHOICES,
                'label' => 'survey.domestic.forms.day-multiple.departed-ports.transferred-from.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => 'survey.domestic.forms.day-multiple.departed-ports.transferred-from.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DomesticStopMultiple::class,
        ]);
    }
}
