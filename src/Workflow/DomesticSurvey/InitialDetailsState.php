<?php

namespace App\Workflow\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class InitialDetailsState extends AbstractFormWizardState
{
    public const string STATE_INTRODUCTION = 'introduction';
    public const string STATE_REQUEST_CONTACT_DETAILS = 'contact-details';
    public const string STATE_ASK_IN_POSSESSION = 'in-possession';
    public const string STATE_ASK_HIREE_DETAILS = 'hiree-details';
    public const string STATE_ASK_SCRAPPED_DETAILS = 'scrapped-details';
    public const string STATE_ASK_SOLD_DETAILS = 'sold-details';
    public const string STATE_END = 'end';
    public const string STATE_CHANGE_CONTACT_DETAILS = 'change-contact-details';

    private SurveyResponse $subject;

    #[\Override]
    public function getSubject(): SurveyResponse
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if (!$subject instanceof SurveyResponse) {
            throw new InvalidArgumentException('Got ' . ($subject ? $subject::class : 'null') . ', expected ' . SurveyResponse::class);
        }
        $this->subject = $subject;
        return $this;
    }
}
