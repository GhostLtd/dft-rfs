<?php

namespace App\Form\DomesticSurvey\DayMulti;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class ArrivedPortsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('transferredTo', Gds\ChoiceType::class, [
                'choices' => Day::TRANSFER_CHOICES,
                'label' => 'survey.domestic.forms.day-multiple.arrived-ports.transferred-to.label',
                'label_attr' => ['class' => 'govuk-label--m'],
                'help' => 'survey.domestic.forms.day-multiple.arrived-ports.transferred-to.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => DayStop::class,
        ]);
    }
}