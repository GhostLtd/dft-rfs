<?php

namespace App\Form;

use App\Utility\HazardousGoodsHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractHazardousGoodsType extends AbstractType
{
    protected $hazardousGoodsHelper;

    public function __construct(HazardousGoodsHelper $hazardousGoodsHelper)
    {
        $this->hazardousGoodsHelper = $hazardousGoodsHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        [$choices, $choiceOptions] = $this->hazardousGoodsHelper->getFormChoicesAndOptions();

        $translationKeyPrefix = "{$options['translation_entity_key']}.hazardous-goods";
        $builder
            ->add('hazardousGoodsCode', Gds\ChoiceType::class, [
                'expanded' => true,
                'choices' => $choices,
                'choice_options' => $choiceOptions,
                'label' => "{$translationKeyPrefix}.hazardous-goods.label",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-label--xl'],
                'help' => "{$translationKeyPrefix}.hazardous-goods.help",
            ])
            ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => 'hazardous-goods',
        ]);
        $resolver->setRequired(["translation_entity_key"]);
    }
}
