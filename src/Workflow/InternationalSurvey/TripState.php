<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Trip;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class TripState extends AbstractFormWizardState
{
    public const string STATE_ORIGIN_AND_DESTINATION = 'origin-and-destination';
    public const string STATE_DATES = 'dates';
    public const string STATE_OUTBOUND_PORTS = 'outbound-ports';
    public const string STATE_RETURN_PORTS = 'return-ports';
    public const string STATE_CHANGED_BODY_TYPE = 'body-type';
    public const string STATE_SWAPPED_TRAILER = 'swapped-trailer';
    public const string STATE_NEW_VEHICLE_WEIGHTS = 'vehicle-weights';
    public const string STATE_DISTANCE = 'distance';
    public const string STATE_COUNTRIES_TRANSITTED = 'countries-transitted';
    public const string STATE_SUMMARY = 'summary';

    private Trip $subject;

    public function getSubject(): ?Trip
    {
        return $this->subject ?? null;
    }

    public function setSubject($subject): self
    {
        if (!$subject instanceof Trip) {
            throw new InvalidArgumentException('Got ' . $subject::class . ', expected ' . Trip::class);
        }
        $this->subject = $subject;
        return $this;
    }
}
