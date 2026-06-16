<?php

namespace App\Form\DomesticSurvey\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Form\Validator\Postcode;
use App\Utility\PostcodeChangeoverHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class FurthestStopType extends AbstractType
{
    public function __construct(protected PostcodeChangeoverHelper $changeoverHelper)
    {
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $translationKeyPrefix = "domestic.day-summary.furthest-stop";

            $form = $event->getForm();
            /** @var DaySummary|null $data */
            $data = $event->getData();

            if (!$data) {
                return;
            }

            $survey = $data->getDay()?->getResponse()?->getSurvey();
            $isPostcodeOnly = $this->changeoverHelper->isPostcodeOnly($survey);

            if ($isPostcodeOnly) {
                $form->add('furthestStop', Gds\InputType::class, [
                    'label' => "{$translationKeyPrefix}.furthest-stop.postcode-only.label",
                    'label_is_page_heading' => true,
                    'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                    'help' => "{$translationKeyPrefix}.furthest-stop.postcode-only.help",
                    'constraints' => [new Postcode(allowPartial: true, groups: ['day-summary.furthest-stop'])],
                ]);
            } else {
                $form->add('furthestStop', Gds\InputType::class, [
                    'label' => "{$translationKeyPrefix}.furthest-stop.label",
                    'label_is_page_heading' => true,
                    'label_attr' => ['class' => 'govuk-fieldset__legend--xl'],
                    'help' => "{$translationKeyPrefix}.furthest-stop.help",
                ]);
            }
        });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DaySummary::class,
            'validation_groups' => 'day-summary.furthest-stop'
        ]);
    }
}
