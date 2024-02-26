<?php

namespace App\Workflow\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use App\Form\PreEnquiry\CompanyNameType;
use App\Form\PreEnquiry\CorrespondenceAddressType;
use App\Form\PreEnquiry\CorrespondenceDetailsType;
use App\Form\PreEnquiry\BusinessDetailsType;
use App\Form\PreEnquiry\VehicleQuestionsType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class PreEnquiryState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_COMPANY_NAME = 'company-name';
    public const STATE_CORRESPONDENCE_DETAILS = 'correspondence-details';
    public const STATE_CORRESPONDENCE_ADDRESS = 'correspondence-address';
    public const STATE_VEHICLE_QUESTIONS = 'vehicle-questions';
    public const STATE_BUSINESS_DETAILS = 'business-details';
    public const STATE_SUMMARY = 'summary';

    private const array FORM_MAP = [
        self::STATE_COMPANY_NAME => CompanyNameType::class,
        self::STATE_CORRESPONDENCE_DETAILS => CorrespondenceDetailsType::class,
        self::STATE_CORRESPONDENCE_ADDRESS => CorrespondenceAddressType::class,
        self::STATE_VEHICLE_QUESTIONS => VehicleQuestionsType::class,
        self::STATE_BUSINESS_DETAILS => BusinessDetailsType::class,
    ];

    private const array TEMPLATE_MAP = [
        self::STATE_INTRODUCTION => 'pre_enquiry/introduction.html.twig',
        self::STATE_COMPANY_NAME => 'pre_enquiry/form-company-name.html.twig',
        self::STATE_CORRESPONDENCE_DETAILS => 'pre_enquiry/form-correspondence-details.html.twig',
        self::STATE_CORRESPONDENCE_ADDRESS => 'pre_enquiry/form-correspondence-address.html.twig',
        self::STATE_VEHICLE_QUESTIONS => 'pre_enquiry/form-vehicle-questions.html.twig',
        self::STATE_BUSINESS_DETAILS => 'pre_enquiry/form-business-details.html.twig',
    ];

    /** @var PreEnquiryResponse */
    private $subject;

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== PreEnquiryResponse::class) {
            throw new InvalidArgumentException("Got " . $subject::class . ", expected " . PreEnquiryResponse::class);
        }
        $this->subject = $subject;
        return $this;
    }

    #[\Override]
    public function getStateFormMap()
    {
        return self::FORM_MAP;
    }

    #[\Override]
    public function getStateTemplateMap()
    {
        return self::TEMPLATE_MAP;
    }

    #[\Override]
    public function getDefaultTemplate()
    {
        return null;
    }

    #[\Override]
    public function isValidAlternativeStartState($state): bool
    {
        $response = ($this->subject && $this->subject instanceof PreEnquiryResponse) ? $this->subject : null;
        $isCommitted = $response && !!$response->getId();

        if ($isCommitted) {
            $validStates = [
                PreEnquiryState::STATE_COMPANY_NAME,
                PreEnquiryState::STATE_CORRESPONDENCE_ADDRESS,
                PreEnquiryState::STATE_CORRESPONDENCE_DETAILS,
                PreEnquiryState::STATE_BUSINESS_DETAILS,
                PreEnquiryState::STATE_VEHICLE_QUESTIONS,
            ];

            return in_array($state, $validStates);
        }

        return false;
    }
}