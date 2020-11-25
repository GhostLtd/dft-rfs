<?php

namespace App\Form\DomesticSurvey\DayMulti;

use App\Entity\Domestic\DayStop;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class DepartedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('startLocation', Gds\InputType::class, [
                'label' => 'survey.domestic.forms.day-multiple.departed.location.label',
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => 'survey.domestic.forms.day-multiple.departed.location.help',
            ])
            ->add('goodsLoaded', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'label' => 'survey.domestic.forms.day-multiple.departed.goods-loaded.label',
                'label_attr' => ['class' => 'govuk-label--s'],
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
