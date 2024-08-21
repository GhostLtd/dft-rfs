<?php

namespace App\Form\Admin\InternationalSurvey;

use App\Entity\AbstractGoodsDescription;
use App\Entity\CargoType;
use App\Form\CountryType;
use App\Utility\HazardousGoodsHelper;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionLoadType extends AbstractType
{
    public const CHOICE_YES = 'common.choices.boolean.yes';
    public const CHOICE_NO = 'common.choices.boolean.no';
    public const CHOICES = [
        self::CHOICE_YES => true,
        self::CHOICE_NO => false,
    ];

    public function __construct(protected HazardousGoodsHelper $hazardousGoodsHelper)
    {
    }
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        [$goodsChoices, $goodsChoiceOptions] = AbstractGoodsDescription::getFormChoicesAndOptions(true);
        [$hazardousChoices, $hazardousChoiceOptions] = $this->hazardousGoodsHelper->getFormChoicesAndOptions(true, true, false);
        [$cargoChoices, $cargoChoiceOptions] = CargoType::getFormChoicesAndOptions();

        $addPlaceholder = function(array $choices) use ($options):array {
            if (!$options['placeholders']) {
                return $choices;
            }

            return array_merge(['' => null], $choices);
        };

        $builder
            ->add('name', Gds\InputType::class, [
                'label' => "Place",
                'attr' => ['class' => 'govuk-input--width-10'],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('country', CountryType::class)
            ->add('goodsDescription', Gds\ChoiceType::class, [
                'choices' => $goodsChoices,
                'choice_options' => $goodsChoiceOptions,
                'label' => "Goods carried",
                'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
            ])
            ->add('goodsDescriptionOther', Gds\InputType::class, [
                'label' => false,
                'help' => "goods.description.help.other",
            ])
            ->add('hazardousGoodsCode', Gds\ChoiceType::class, [
                'expanded' => false,
                'choices' => $addPlaceholder($hazardousChoices),
                'choice_options' => $hazardousChoiceOptions,
                'label' => "Were these goods dangerous or hazardous?",
                'label_attr' => ['class' => 'govuk-label--s'],
                'label_html' => true,
            ])
            ->add('cargoTypeCode', Gds\ChoiceType::class, [
                'expanded' => false,
                'choices' => $addPlaceholder($cargoChoices),
                'choice_options' => $cargoChoiceOptions,
                'label' => "How were the goods carried?",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('weightOfGoods', Gds\IntegerType::class, [
                'label' => "Weight of goods loaded",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-input--width-10'],
                'suffix' => 'kg',
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'validation_groups' => ['admin_action_load'],
            'placeholders' => false,
        ]);
    }
}
