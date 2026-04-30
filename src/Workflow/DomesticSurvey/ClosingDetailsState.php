<?php


namespace App\Workflow\DomesticSurvey;

use App\Entity\Domestic\SurveyResponse;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;

class ClosingDetailsState extends AbstractFormWizardState
{
    public const string STATE_START = 'start';
    public const string STATE_EARLY_RESPONSE = 'early-response';
    public const string STATE_VEHICLE_FUEL = 'vehicle-fuel';
    public const string STATE_MISSING_DAYS = 'missing-days';
    public const string STATE_REASON_EMPTY_SURVEY = 'empty-survey';

    public const string STATE_DRIVER_AVAILABILITY_DRIVERS = 'da-drivers';
    public const string STATE_DRIVER_AVAILABILITY_WAGES = 'da-wages';
    public const string STATE_DRIVER_AVAILABILITY_BONUSES = 'da-bonuses';
    public const string STATE_DRIVER_AVAILABILITY_DELIVERIES = 'da-deliveries';

    public const string STATE_CONFIRM = 'confirm';
    public const string STATE_END = 'end';

    private ?bool $isCorrect = null;
    private ?bool $missingDays = null;

    private SurveyResponse $subject;

    #[\Override]
    public function getSubject() : ?SurveyResponse
    {
        return $this->subject ?? null;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== SurveyResponse::class) {
            throw new \InvalidArgumentException("Got " . $subject::class . ", expected " . SurveyResponse::class);
        }

        $this->subject = $subject;
        return $this;
    }

    public function getIsCorrect(): ?bool { return $this->isCorrect; }
    public function setIsCorrect(?bool $value): self { $this->isCorrect = $value; return $this; }

    public function getMissingDays(): ?bool { return $this->missingDays; }
    public function setMissingDays(?bool $value): self { $this->missingDays = $value; return $this; }
}
