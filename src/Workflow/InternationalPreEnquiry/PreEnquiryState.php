<?php

namespace App\Workflow\InternationalPreEnquiry;

use App\Entity\International\PreEnquiryResponse;
use App\Form\InternationalPreEnquiry\CompanyNameType;
use App\Form\InternationalPreEnquiry\CorrespondenceAddressType;
use App\Form\InternationalPreEnquiry\CorrespondenceDetailsType;
use App\Form\InternationalPreEnquiry\EmployeesAndInternationalJourneysType;
use App\Form\InternationalPreEnquiry\VehicleQuestionsType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class PreEnquiryState extends AbstractFormWizardState implements FormWizardStateInterface
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_COMPANY_NAME = 'company-name';
    const STATE_CORRESPONDENCE_DETAILS = 'correspondence-details';
    const STATE_CORRESPONDENCE_ADDRESS = 'correspondence-address';
    const STATE_VEHICLE_QUESTIONS = 'vehicle-questions';
    const STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS = 'employees-and-international-journeys';
    const STATE_SUMMARY = 'summary';

    const STATE_CHANGE_COMPANY_NAME = 'change-company-name';
    const STATE_CHANGE_CORRESPONDENCE_DETAILS = 'change-correspondence-details';
    const STATE_CHANGE_CORRESPONDENCE_ADDRESS = 'change-correspondence-address';
    const STATE_CHANGE_VEHICLE_QUESTIONS = 'change-vehicle-questions';
    const STATE_CHANGE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS = 'change-employees-and-international-journeys';

    private const FORM_MAP = [
        self::STATE_COMPANY_NAME => CompanyNameType::class,
        self::STATE_CORRESPONDENCE_DETAILS => CorrespondenceDetailsType::class,
        self::STATE_CORRESPONDENCE_ADDRESS => CorrespondenceAddressType::class,
        self::STATE_VEHICLE_QUESTIONS => VehicleQuestionsType::class,
        self::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS => EmployeesAndInternationalJourneysType::class,

        self::STATE_CHANGE_COMPANY_NAME => CompanyNameType::class,
        self::STATE_CHANGE_CORRESPONDENCE_DETAILS => CorrespondenceDetailsType::class,
        self::STATE_CHANGE_CORRESPONDENCE_ADDRESS => CorrespondenceAddressType::class,
        self::STATE_CHANGE_VEHICLE_QUESTIONS => VehicleQuestionsType::class,
        self::STATE_CHANGE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS => EmployeesAndInternationalJourneysType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_INTRODUCTION => 'international_pre_enquiry/introduction.html.twig',
        self::STATE_COMPANY_NAME => 'international_pre_enquiry/form-business-details.html.twig',
        self::STATE_CORRESPONDENCE_DETAILS => 'international_pre_enquiry/form-correspondence-details.html.twig',
        self::STATE_CORRESPONDENCE_ADDRESS => 'international_pre_enquiry/form-correspondence-address.html.twig',
        self::STATE_VEHICLE_QUESTIONS => 'international_pre_enquiry/form-vehicle-questions.html.twig',
        self::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS => 'international_pre_enquiry/form-employees-and-international-journeys.html.twig',

        self::STATE_CHANGE_COMPANY_NAME => 'international_pre_enquiry/form-business-details.html.twig',
        self::STATE_CHANGE_CORRESPONDENCE_DETAILS => 'international_pre_enquiry/form-correspondence-details.html.twig',
        self::STATE_CHANGE_CORRESPONDENCE_ADDRESS => 'international_pre_enquiry/form-correspondence-address.html.twig',
        self::STATE_CHANGE_VEHICLE_QUESTIONS => 'international_pre_enquiry/form-vehicle-questions.html.twig',
        self::STATE_CHANGE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS => 'international_pre_enquiry/form-employees-and-international-journeys.html.twig',
    ];

    /** @var PreEnquiryResponse */
    private $subject;

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === PreEnquiryResponse::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . PreEnquiryResponse::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function getStateFormMap()
    {
        return self::FORM_MAP;
    }

    public function getStateTemplateMap()
    {
        return self::TEMPLATE_MAP;
    }

    public function getDefaultTemplate()
    {
        return null;
    }
}