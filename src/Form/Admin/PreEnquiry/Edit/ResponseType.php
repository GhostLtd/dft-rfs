<?php

namespace App\Form\Admin\PreEnquiry\Edit;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Entity\SurveyResponse as AbstractSurveyResponse;
use App\Form\LongAddressType;
use Ghost\GovUkFrontendBundle\Form\Type as Gds;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataMapperInterface;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ResponseType extends AbstractType implements DataMapperInterface
{
    const CHOICE_YES = 'common.choices.boolean.yes';
    const CHOICE_NO = 'common.choices.boolean.no';
    const CHOICES = [
        self::CHOICE_YES => 'yes',
        self::CHOICE_NO => 'no',
    ];

    private const nonListenerFields = ['contactName', 'contactTelephone', 'contactEmail', 'totalVehicleCount', 'internationalJourneyVehicleCount', 'numberOfEmployees', 'annualJourneyEstimate'];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $companyNamePrefix = 'pre-enquiry.company-name';
        $correspondenceDetailsPrefix = "pre-enquiry.correspondence-details";
        $correspondenceAddressPrefix = "pre-enquiry.correspondence-address";
        $businessDetailsPrefix = "pre-enquiry.business-details";
        $vehicleQuestionsPrefix = "pre-enquiry.vehicle-questions";

        $builder
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent) use ($companyNamePrefix, $correspondenceAddressPrefix) {
                $form = $formEvent->getForm();
                $data = $formEvent->getData();

                assert($data instanceof PreEnquiryResponse);
                $expectedCompanyName = $data->getPreEnquiry()->getCompanyName();

                if ($data->getPreEnquiry()->hasInvitationAddress()) {
                    $form
                        ->add('isCorrectAddress', Gds\ChoiceType::class, [
                            'label' => "{$correspondenceAddressPrefix}.correct-address.label",
                            'label_attr' => [
                                'class' => 'govuk-label--s',
                            ],
                            'help' => "{$correspondenceAddressPrefix}.correct-address.help",
                            'choices' => self::CHOICES,
                            'choice_options' => [
                                self::CHOICE_NO => [
                                    'conditional_form_name' => 'contactAddress',
                                ],
                            ],
                            'validation_groups' => ['Default'],
                        ]);
                }

                $form
                    ->add('isCorrectCompanyName', Gds\ChoiceType::class, [
                        'label' => "{$companyNamePrefix}.correct-company-name.label",
                        'label_attr' => [
                            'class' => 'govuk-label--s',
                        ],
                        'label_translation_parameters' => [
                            'expectedCompanyName' => $expectedCompanyName,
                        ],
                        'help' => "{$companyNamePrefix}.correct-company-name.help",
                        'choices' => self::CHOICES,
                        'choice_options' => [
                            self::CHOICE_NO => [
                                'conditional_form_name' => 'companyName',
                            ],
                        ],
                        'validation_groups' => ['Default'],
                    ])
                    ->add('companyName', Gds\InputType::class, [
                        'label' => "{$companyNamePrefix}.company-name.label",
                        'help' => "{$companyNamePrefix}.company-name.help",
                        'label_attr' => ['class' => 'govuk-label--s'],
                    ])
                    ->add('contactAddress', LongAddressType::class, [
                        'label' => "{$correspondenceAddressPrefix}.address.label",
                        'label_attr' => [
                            'class' => 'govuk-label--m',
                        ],
                        'help' => "{$correspondenceAddressPrefix}.address.help",
                        'include_addressee' => false,
                    ]);
            })
            ->add('contactName', Gds\InputType::class, [
                'label' => "{$correspondenceDetailsPrefix}.name.label",
                'help' => "{$correspondenceDetailsPrefix}.name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('contactTelephone', Gds\InputType::class, [
                'label' => "{$correspondenceDetailsPrefix}.phone.label",
                'help' => "{$correspondenceDetailsPrefix}.phone.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('contactEmail', Gds\EmailType::class, [
                'label' => "{$correspondenceDetailsPrefix}.email.label",
                'help' => "{$correspondenceDetailsPrefix}.email.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('totalVehicleCount', Gds\NumberType::class, [
                'label' => "{$vehicleQuestionsPrefix}.total-vehicle-count.label",
                'help' => "{$vehicleQuestionsPrefix}.total-vehicle-count.help",
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('internationalJourneyVehicleCount', Gds\NumberType::class, [
                'label' => "{$vehicleQuestionsPrefix}.international-journey-vehicle-count.label",
                'help' => "{$vehicleQuestionsPrefix}.international-journey-vehicle-count.help",
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('annualJourneyEstimate', Gds\NumberType::class, [
                'label' => "{$vehicleQuestionsPrefix}.annual-journey-estimate.label",
                'help' => "{$vehicleQuestionsPrefix}.annual-journey-estimate.help",
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('numberOfEmployees', Gds\ChoiceType::class, [
                'choices' => AbstractSurveyResponse::EMPLOYEES_CHOICES,
                'placeholder' => '',
                'label' => "{$businessDetailsPrefix}.number-of-employees.label",
                'help' => "{$businessDetailsPrefix}.number-of-employees.help",
                'label_attr' => ['class' => 'govuk-label--s'],
                'attr' => ['class' => 'govuk-select--width-15'],
                'expanded' => false,
            ])
            ->add('submit', Gds\ButtonType::class, [
                'label' => 'Save changes',
            ])
            ->add('cancel', Gds\ButtonType::class, [
                'label' => 'Cancel',
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => PreEnquiryResponse::class,
            'validation_groups' => function(FormInterface $form) {
                $data = $form->getData();
                $groups = ['company_name', 'correspondence_details', 'employees_and_international_journeys', 'is_correct_address', 'vehicle_questions', 'business_details'];

                if ($data instanceof PreEnquiryResponse) {
                    if ($data->getIsCorrectAddress() === false) {
                        $groups[] = 'correspondence_address';
                    }
                }

                return $groups;
            }
        ]);
    }

    public function mapDataToForms($viewData, $forms)
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

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach (self::nonListenerFields as $field) {
            $forms[$field]->setData($accessor->getValue($viewData, $field));
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

        if (isset($forms['isCorrectAddress'])) {
            $isCorrectAddress = $viewData->getIsCorrectAddress();
            if ($isCorrectAddress !== null) {
                $forms['isCorrectAddress']->setData(self::CHOICES[$isCorrectAddress ? self::CHOICE_YES : self::CHOICE_NO]);
            }
        }

        $forms['contactAddress']->setData($viewData->getIsCorrectAddress() ? null : $viewData->getContactAddress());
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        assert($viewData instanceof PreEnquiryResponse);

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach (self::nonListenerFields as $field) {
            $accessor->setValue($viewData, $field, $forms[$field]->getData());
        }

        $isCorrectCompanyName = $forms['isCorrectCompanyName']->getData();

        if ($isCorrectCompanyName === self::CHOICES[self::CHOICE_YES]) {
            $viewData->setCompanyName($viewData->getPreEnquiry()->getCompanyName());
            $viewData->setIsCorrectCompanyName(true);
        } else if ($isCorrectCompanyName === self::CHOICES[self::CHOICE_NO]) {
            $viewData->setCompanyName($forms['companyName']->getData());
            $viewData->setIsCorrectCompanyName(false);
        } else {
            $viewData->setCompanyName(null);
            $viewData->setIsCorrectCompanyName(null);
        }

        if (isset($forms['isCorrectAddress'])) {
            $isCorrectAddress = $forms['isCorrectAddress']->getData();

            if ($isCorrectAddress !== null) {
                $isCorrectAddress = $isCorrectAddress === self::CHOICES[self::CHOICE_YES];
            }
        } else {
            $isCorrectAddress = false;
        }

        $address = $isCorrectAddress ?
            (clone $viewData->getPreEnquiry()->getInvitationAddress()) :
            $forms['contactAddress']->getData();

        $viewData
            ->setContactAddress($address)
            ->setIsCorrectAddress($isCorrectAddress);

        $viewData->getContactAddress()->setLine1($viewData->getCompanyName());
    }
}
