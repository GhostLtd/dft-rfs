<?php

namespace App\Form\Admin\PreEnquiry\Edit;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Form\AddressType;
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

    private const nonListenerFields = ['correspondenceName', 'phone', 'email', 'correspondenceAddress', 'totalVehicleCount', 'internationalJourneyVehicleCount', 'employeeCount', 'annualJourneyEstimate'];

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $businessDetailsPrefix = 'pre-enquiry.business-details';
        $correspondenceDetailsPrefix = "pre-enquiry.correspondence-details";
        $correspondenceAddressPrefix = "pre-enquiry.correspondence-address";
        $employeesAndJourneysPrefix = "pre-enquiry.employees-and-international-journeys";
        $vehicleQuestionsPrefix = "pre-enquiry.vehicle-questions";

        $builder
            ->setDataMapper($this)
            ->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $formEvent) use ($businessDetailsPrefix) {
                $form = $formEvent->getForm();
                $data = $formEvent->getData();

                assert($data instanceof PreEnquiryResponse);
                $expectedCompanyName = $data->getPreEnquiry()->getCompany()->getBusinessName();

                $form
                    ->add('isCorrectCompanyName', Gds\ChoiceType::class, [
                        'label' => "{$businessDetailsPrefix}.correct-company-name.label",
                        'label_attr' => [
                            'class' => 'govuk-label--s',
                        ],
                        'label_translation_parameters' => [
                            'expectedCompanyName' => $expectedCompanyName,
                        ],
                        'help' => "{$businessDetailsPrefix}.correct-company-name.help",
                        'choices' => self::CHOICES,
                        'choice_options' => [
                            self::CHOICE_NO => [
                                'conditional_form_name' => 'companyName',
                            ],
                        ],
                        'validation_groups' => ['Default'],
                    ])
                    ->add('companyName', Gds\InputType::class, [
                        'label' => "{$businessDetailsPrefix}.company-name.label",
                        'help' => "{$businessDetailsPrefix}.company-name.help",
                        'label_attr' => ['class' => 'govuk-label--s'],
                    ]);
            })
            ->add('correspondenceName', Gds\InputType::class, [
                'label' => "{$correspondenceDetailsPrefix}.name.label",
                'help' => "{$correspondenceDetailsPrefix}.name.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('phone', Gds\InputType::class, [
                'label' => "{$correspondenceDetailsPrefix}.phone.label",
                'help' => "{$correspondenceDetailsPrefix}.phone.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('email', Gds\EmailType::class, [
                'label' => "{$correspondenceDetailsPrefix}.email.label",
                'help' => "{$correspondenceDetailsPrefix}.email.help",
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('correspondenceAddress', AddressType::class, [
                'label' => "{$correspondenceAddressPrefix}.address.label",
                'label_attr' => [
                    'class' => 'govuk-label--m',
                ],
                'help' => "{$correspondenceAddressPrefix}.address.help",
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
            ->add('employeeCount', Gds\NumberType::class, [
                'label' => "{$employeesAndJourneysPrefix}.employee-count.label",
                'help' => "{$employeesAndJourneysPrefix}.employee-count.help",
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
            ])
            ->add('annualJourneyEstimate', Gds\NumberType::class, [
                'label' => "{$employeesAndJourneysPrefix}.annual-journey-estimate.label",
                'help' => "{$employeesAndJourneysPrefix}.annual-journey-estimate.help",
                'attr' => [
                    'class' => 'govuk-input--width-3',
                ],
                'label_attr' => ['class' => 'govuk-label--s'],
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
            'validation_groups' => ['company_name', 'correspondence_address', 'correspondence_details', 'employees_and_international_journeys', 'vehicle_questions'],
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
        foreach(self::nonListenerFields as $field) {
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
    }

    public function mapFormsToData($forms, &$viewData)
    {
        $forms = iterator_to_array($forms);
        /** @var FormInterface[] $forms */

        assert($viewData instanceof PreEnquiryResponse);

        $accessor = PropertyAccess::createPropertyAccessor();
        foreach(self::nonListenerFields as $field) {
            $accessor->setValue($viewData, $field, $forms[$field]->getData());
        }

        $isCorrectCompanyName = $forms['isCorrectCompanyName']->getData();

        if ($isCorrectCompanyName === self::CHOICES[self::CHOICE_YES]) {
            $viewData->setCompanyName($viewData->getPreEnquiry()->getCompany()->getBusinessName());
            $viewData->setIsCorrectCompanyName(true);
        } else if ($isCorrectCompanyName === self::CHOICES[self::CHOICE_NO]) {
            $viewData->setCompanyName($forms['companyName']->getData());
            $viewData->setIsCorrectCompanyName(false);
        } else {
            $viewData->setCompanyName(null);
            $viewData->setIsCorrectCompanyName(null);
        }
    }
}
