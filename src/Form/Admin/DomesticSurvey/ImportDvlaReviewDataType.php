<?php


namespace App\Form\Admin\DomesticSurvey;


use App\Utility\DvlaImporter;
use App\Utility\RegistrationMarkHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;

class ImportDvlaReviewDataType extends AbstractType
{
    protected function choiceLabel($data) {
        $regMark = new RegistrationMarkHelper($data[DvlaImporter::COL_REG_MARK]);
        $address1 = ucwords(strtolower($data[DvlaImporter::COL_ADDRESS_1]));
        return "<b>{$regMark->getFormattedRegistrationMark()}</b> {$address1}, {$data[DvlaImporter::COL_POSTCODE]}";
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function(FormEvent $event) use ($options) {
            $choices = array_combine(array_map([$this, 'choiceLabel'], $options['dvla_data']), array_keys($options['dvla_data']));

            $choiceOptions = $choices;
            foreach ($choiceOptions as $k=>$v) {
                $choiceOptions[$k] = [
                    'label_html' => true,
                ];
            }

            $event->getForm()
                ->add('review_data', Gds\ChoiceType::class, [
                    'data' => array_keys($options['dvla_data']),
                    'multiple' => true,
                    'help' => "Imported from <kbd>{$options['dvla_filename']}</kbd>",
                    'help_html' => true,
                    'label' => 'Data found in DVLA file',
                    'label_attr' => ['class' => 'govuk-label--m'],
                    'attr' => [
                        'class' => 'govuk-checkboxes--small govuk-import-dvla-review',
                    ],
                    'choice_options' => $choiceOptions,
                    'choices' => $choices,
                ])
                ->add('submit', Gds\ButtonType::class, ['type' => 'submit'])
            ;
        });
        // create a form

    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired([
            'dvla_data',
            'dvla_filename',
        ]);
    }
}