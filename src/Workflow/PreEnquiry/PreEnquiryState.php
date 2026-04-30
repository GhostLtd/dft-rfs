<?php

namespace App\Workflow\PreEnquiry;

use App\Entity\PreEnquiry\PreEnquiryResponse;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class PreEnquiryState extends AbstractFormWizardState
{
    public const string STATE_INTRODUCTION = 'introduction';
    public const string STATE_COMPANY_NAME = 'company-name';
    public const string STATE_CORRESPONDENCE_DETAILS = 'correspondence-details';
    public const string STATE_CORRESPONDENCE_ADDRESS = 'correspondence-address';
    public const string STATE_HGV_DETAILS = 'hgv-details';
    public const string STATE_LGV_DETAILS = 'lgv-details';
    public const string STATE_BUSINESS_DETAILS = 'business-details';
    public const string STATE_SUMMARY = 'summary';

    protected ?PreEnquiryResponse $subject = null;

    public function getSubject(): ?PreEnquiryResponse
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!$subject instanceof PreEnquiryResponse) {
            throw new InvalidArgumentException('Got ' . $subject::class . ', expected ' . PreEnquiryResponse::class);
        }

        $this->subject = $subject;
        return $this;
    }
}
