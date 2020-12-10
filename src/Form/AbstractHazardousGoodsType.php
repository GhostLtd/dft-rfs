<?php

namespace App\Form;

use App\Entity\HazardousGoods;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractHazardousGoodsType extends AbstractType
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choiceOptions = [];
        $choices = [];

        foreach (HazardousGoods::CHOICES as $k=>$v) {
            $labelPrefix = ($v === '0') ? '' : "<b>{$v}</b> ";

            // Used escape rules from:
            // vendor/twig/twig/src/Extension/EscaperExtension.php : 232
            $safeLabel = htmlspecialchars(
                $this->translator->trans("goods.hazardous.{$v}"),
                ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $label = $labelPrefix . $safeLabel;

            $choices[$label] = $v;

            $choiceOptions[$label] = [
                'label_html' => true,
            ];
        }

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
        $resolver->setAllowedValues("translation_entity_key", ['domestic.day-summary', 'domestic.day-stop', 'international.[change me]']);
    }
}
