<?php

namespace App\Workflow\InternationalPreEnquiry;

use App\Entity\InternationalPreEnquiryResponse;
use App\Form\InternationalPreEnquiry\CompanyNameType;
use App\Form\InternationalPreEnquiry\CorrespondenceAddressType;
use App\Form\InternationalPreEnquiry\CorrespondenceDetailsType;
use App\Form\InternationalPreEnquiry\EmployeesAndInternationalJourneysType;
use App\Form\InternationalPreEnquiry\VehicleQuestionsType;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class PreEnquiryState implements FormWizardInterface
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_COMPANY_NAME = 'company-name';
    const STATE_CORRESPONDENCE_DETAILS = 'correspondence-details';
    const STATE_CORRESPONDENCE_ADDRESS = 'correspondence-address';
    const STATE_VEHICLE_QUESTIONS = 'vehicle-questions';
    const STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS = 'employees-and-international-journeys';
    const STATE_END = 'end';

    private const FORM_MAP = [
        self::STATE_COMPANY_NAME => CompanyNameType::class,
        self::STATE_CORRESPONDENCE_DETAILS => CorrespondenceDetailsType::class,
        self::STATE_CORRESPONDENCE_ADDRESS => CorrespondenceAddressType::class,
        self::STATE_VEHICLE_QUESTIONS => VehicleQuestionsType::class,
        self::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS => EmployeesAndInternationalJourneysType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_INTRODUCTION => 'international_pre_enquiry/introduction.html.twig',
        self::STATE_COMPANY_NAME => 'international_pre_enquiry/form-business-details.html.twig',
        self::STATE_CORRESPONDENCE_DETAILS => 'international_pre_enquiry/form-correspondence-details.html.twig',
        self::STATE_CORRESPONDENCE_ADDRESS => 'international_pre_enquiry/form-correspondence-address.html.twig',
        self::STATE_VEHICLE_QUESTIONS => 'international_pre_enquiry/form-vehicle-questions.html.twig',
        self::STATE_EMPLOYEES_AND_INTERNATIONAL_JOURNEYS => 'international_pre_enquiry/form-employees-and-international-journeys.html.twig',
    ];

    private $state = self::STATE_COMPANY_NAME;

    /** @var InternationalPreEnquiryResponse */
    private $subject;

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     * @return self
     */
    public function setState($state): self
    {
        $this->state = $state;
        return $this;
    }

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === InternationalPreEnquiryResponse::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . InternationalPreEnquiryResponse::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function isValidJumpInState($state)
    {
        return (in_array($state, $this->getValidJumpInStates()));
    }

    protected function getValidJumpInStates()
    {
        $states = [self::STATE_INTRODUCTION, self::STATE_COMPANY_NAME];

        if ($this->subject->getCompanyName()) {
            $states[] = self::STATE_CORRESPONDENCE_DETAILS;

            if ($this->subject->getCorrespondenceName() || $this->subject->getEmail() || $this->subject->getPhone()) {
                $states[] = self::STATE_CORRESPONDENCE_ADDRESS;

                if ($this->subject->getCorrespondenceAddress()) {
                    $states[] = self::STATE_VEHICLE_QUESTIONS;
                }
            }
        }

        return $states;
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