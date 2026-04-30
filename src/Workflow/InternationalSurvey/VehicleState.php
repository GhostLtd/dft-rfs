<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Vehicle;
use Ghost\GovUkCoreBundle\FormWizard\AbstractFormWizardState;
use InvalidArgumentException;

class VehicleState extends AbstractFormWizardState
{
    public const string STATE_REQUEST_VEHICLE_DETAILS = 'vehicle-registration';
    public const string STATE_REQUEST_CONFIRM_DATES = 'confirm-dates';
    public const string STATE_REQUEST_VEHICLE_TRAILER_CONFIGURATION = 'trailer-configuration';
    public const string STATE_REQUEST_VEHICLE_AXLE_CONFIGURATION = 'axle-configuration';
    public const string STATE_REQUEST_VEHICLE_BODY = 'vehicle-body';
    public const string STATE_REQUEST_VEHICLE_WEIGHT = 'vehicle-weight';

    public const string STATE_CHANGE_VEHICLE_DETAILS = 'change-vehicle-registration';
    public const string STATE_CHANGE_VEHICLE_TRAILER_CONFIGURATION = 'change-trailer-configuration';
    public const string STATE_CHANGE_VEHICLE_AXLE_CONFIGURATION = 'change-axle-configuration';
    public const string STATE_CHANGE_VEHICLE_BODY = 'change-vehicle-body';
    public const string STATE_CHANGE_VEHICLE_WEIGHT = 'change-vehicle-weight';

    public const string STATE_SUMMARY = 'summary';

    private Vehicle $subject;

    private ?bool $confirm = null;

    public function getSubject(): ?Vehicle
    {
        return $this->subject ?? null;
    }

    public function setSubject($subject): self
    {
        if (!$subject instanceof Vehicle) {
            throw new InvalidArgumentException('Got ' . $subject::class . ', expected ' . Vehicle::class);
        }
        $this->subject = $subject;
        return $this;
    }

    public function getConfirm(): ?bool { return $this->confirm; }
    public function setConfirm(?bool $value): self { $this->confirm = $value; return $this; }
}
