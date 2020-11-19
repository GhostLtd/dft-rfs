<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\StopTrait;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class OriginPortsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('goodsTransferredFrom', Gds\ChoiceType::class, [
                'choices' => Day::TRANSFER_CHOICES,
                'label' => 'survey.domestic.forms.day-summary.goods-transferred-from.label',
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                'help' => 'survey.domestic.forms.day-summary.goods-transferred-from.help',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StopTrait::class,
        ]);
    }
}
