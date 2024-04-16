<?php

namespace App\Form\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CompanyNameType extends AbstractType implements DataMapperInterface
{
    public const CHOICE_YES = 'common.choices.boolean.yes';
    public const CHOICE_NO = 'common.choices.boolean.no';
    public const CHOICES = [
        self::CHOICE_YES => 'yes',
        self::CHOICE_NO => 'no',
    ];

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent) {
                $form = $formEvent->getForm();
                $data = $formEvent->getData();

                assert($data instanceof PreEnquiryResponse);
                $expectedCompanyName = $data->getPreEnquiry()->getCompanyName();

                $translationPrefix = 'pre-enquiry.company-name';
                $correctNamePrefix = "{$translationPrefix}.correct-company-name";
                $companyNamePrefix = "{$translationPrefix}.company-name";

                $form
                    ->add('isCorrectCompanyName', Gds\ChoiceType::class, [
                        'label' => "{$correctNamePrefix}.label",
                        'label_attr' => ['class' => 'govuk-fieldset__legend--s'],
                        'label_translation_parameters' => [
                            'expectedCompanyName' => $expectedCompanyName,
                        ],
                        'help' => "{$correctNamePrefix}.help",
                        'choices' => self::CHOICES,
                        'choice_options' => [
                            self::CHOICE_NO => [
                                'conditional_form_name' => 'companyName',
                            ],
                        ],
                        'validation_groups' => ['Default'],
                    ])
                    ->add('companyName', Gds\InputType::class, [
                        'label' => "{$companyNamePrefix}.label",
                        'help' => "{$companyNamePrefix}.help",
                        'label_attr' => ['class' => 'govuk-label--s'],
                    ]);
            });
    }

    #[\Override]
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PreEnquiryResponse::class,
            'validation_groups' => ['company_name'],
        ]);
    }

    #[\Override]
    public function mapDataToForms($viewData, $forms): void
    {
        // there is no data yet, so nothing to pre-populate
        if (null === $viewData) {
            return;
        }

        // invalid data type
        if (!$viewData instanceof PreEnquiryResponse) {
            throw new UnexpectedTypeException($viewData, PreEnquiryResponse::class);
        }

        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        if (!isset($forms['isCorrectCompanyName'])) {
            // The data mapper will get called twice - once for continue/cancel buttons and
            // once for the form elements added in the listener.
            return;
        }

        $isCorrectCompanyName = $viewData->getIsCorrectCompanyName();

        if ($isCorrectCompanyName === false) {
            $forms['companyName']->setData($viewData->getCompanyName());
            $forms['isCorrectCompanyName']->setData(self::CHOICES[self::CHOICE_NO]);
        } else {
            $forms['companyName']->setData('');

            if ($isCorrectCompanyName === true) {
                $forms['isCorrectCompanyName']->setData(self::CHOICES[self::CHOICE_YES]);
            }
        }
    }

    #[\Override]
    public function mapFormsToData($forms, &$viewData): void
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        assert($viewData instanceof PreEnquiryResponse);

        $isCorrectCompanyName = $forms['isCorrectCompanyName']->getData();

        if ($isCorrectCompanyName === self::CHOICES[self::CHOICE_YES]) {
            $companyName = $viewData->getPreEnquiry()->getCompanyName();

            $viewData->setCompanyName($companyName);
            $viewData->setIsCorrectCompanyName(true);
        } else if ($isCorrectCompanyName === self::CHOICES[self::CHOICE_NO]) {
            $companyName = $forms['companyName']->getData();

            $viewData->setCompanyName($companyName);
            $viewData->setIsCorrectCompanyName(false);
        } else {
            $companyName = null;
            $viewData->setCompanyName(null);
            $viewData->setIsCorrectCompanyName(null);
        }

        $address = $viewData->getContactAddress();
        if ($companyName !== null && $address !== null) {
            $address->setLine1($companyName);
        }
    }
}
