<?php

namespace App\Form\DomesticSurvey;

use App\Entity\Domestic\BorderCrossingInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

abstract class AbstractBorderCrossingType extends AbstractType implements DataMapperInterface
{
    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $translationKeyPrefix = "domestic.{$options['translation_entity_key']}.border-crossing";
        $builder
            ->add('borderCrossed', Gds\ChoiceType::class, [
                'choices' => ['Yes' => true, 'No' => false],
                'choice_options' => ['Yes' => ['conditional_form_name' => 'borderCrossingLocation']],
                'label' => "{$translationKeyPrefix}.border-crossed.label",
                'help' => "{$translationKeyPrefix}.border-crossed.help",
                'label_is_page_heading' => true,
                'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
            ])
            ->add('borderCrossingLocation', Gds\InputType::class, [
                'label' => "{$translationKeyPrefix}.border-crossing-location.label",
                'label_attr' => ['class' => 'govuk-label--s'],
                'help' => "{$translationKeyPrefix}.border-crossing-location.help",
            ])
            ->setDataMapper($this)
        ;
    }

    use StopTypeTrait {
        configureOptions as traitConfigureOptions;
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $this->traitConfigureOptions($resolver);

        $resolver->setDefaults([
            'validation_groups' => 'border-crossing',
        ]);
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof BorderCrossingInterface) {
            throw new UnexpectedTypeException($viewData, BorderCrossingInterface::class);
        }

        if (!isset($forms['borderCrossed'])) {
            return;
        }

        $forms['borderCrossed']->setData($viewData->getBorderCrossed());
        $forms['borderCrossingLocation']->setData($viewData->getBorderCrossingLocation());
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!$viewData instanceof BorderCrossingInterface) {
            throw new UnexpectedTypeException($viewData, BorderCrossingInterface::class);
        }

        if (!isset($forms['borderCrossed'])) {
            return;
        }

        $borderCrossed = $forms['borderCrossed']->getData();
        $viewData->setBorderCrossed($borderCrossed);

        $borderCrossingLocation = ($borderCrossed === false) ?
            null :
            $forms['borderCrossingLocation']->getData();

        $viewData->setBorderCrossingLocation($borderCrossingLocation);
    }
}
