<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Trip\CountriesTransittedType;
use App\Form\InternationalSurvey\Trip\DatesType;
use App\Form\InternationalSurvey\Trip\DistanceType;
use App\Form\InternationalSurvey\Trip\OutboundCargoStateType;
use App\Form\InternationalSurvey\Trip\OutboundPortsType;
use App\Form\InternationalSurvey\Trip\ReturnCargoStateType;
use App\Form\InternationalSurvey\Trip\ReturnPortsType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class TripState extends AbstractFormWizardState implements FormWizardInterface
{
    const STATE_REQUEST_TRIP_DATES = 'dates';
    const STATE_REQUEST_TRIP_OUTBOUND_PORTS = 'outbound-ports';
    const STATE_REQUEST_TRIP_OUTBOUND_CARGO_STATE = 'outbound-cargo-state';
    const STATE_REQUEST_TRIP_RETURN_PORTS = 'return-ports';
    const STATE_REQUEST_TRIP_RETURN_CARGO_STATE = 'return-cargo-state';
    const STATE_REQUEST_TRIP_DISTANCE = 'distance';
    const STATE_REQUEST_TRIP_COUNTRIES_TRANSITTED = 'countries-transitted';

    const STATE_SUMMARY = 'summary';

    const STATE_CHANGE_TRIP_DATES = 'change-dates';
    const STATE_CHANGE_TRIP_OUTBOUND_PORTS = 'change-outbound-ports';
    const STATE_CHANGE_TRIP_OUTBOUND_CARGO_STATE = 'change-outbound-cargo-state';
    const STATE_CHANGE_TRIP_RETURN_PORTS = 'change-return-ports';
    const STATE_CHANGE_TRIP_RETURN_CARGO_STATE = 'change-return-cargo-state';
    const STATE_CHANGE_TRIP_DISTANCE = 'change-distance';
    const STATE_CHANGE_TRIP_COUNTRIES_TRANSITTED = 'change-countries-transitted';

    private const FORM_MAP = [
        self::STATE_REQUEST_TRIP_DATES => DatesType::class,
        self::STATE_REQUEST_TRIP_OUTBOUND_PORTS => OutboundPortsType::class,
        self::STATE_REQUEST_TRIP_RETURN_PORTS => ReturnPortsType::class,
        self::STATE_REQUEST_TRIP_OUTBOUND_CARGO_STATE => OutboundCargoStateType::class,
        self::STATE_REQUEST_TRIP_RETURN_CARGO_STATE => ReturnCargoStateType::class,
        self::STATE_REQUEST_TRIP_DISTANCE => DistanceType::class,
        self::STATE_REQUEST_TRIP_COUNTRIES_TRANSITTED => CountriesTransittedType::class,

        self::STATE_CHANGE_TRIP_DATES => DatesType::class,
        self::STATE_CHANGE_TRIP_OUTBOUND_PORTS => OutboundPortsType::class,
        self::STATE_CHANGE_TRIP_RETURN_PORTS => ReturnPortsType::class,
        self::STATE_CHANGE_TRIP_OUTBOUND_CARGO_STATE => OutboundCargoStateType::class,
        self::STATE_CHANGE_TRIP_RETURN_CARGO_STATE => ReturnCargoStateType::class,
        self::STATE_CHANGE_TRIP_DISTANCE => DistanceType::class,
        self::STATE_CHANGE_TRIP_COUNTRIES_TRANSITTED => CountriesTransittedType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_REQUEST_TRIP_DATES => 'international_survey/trip/form-dates.html.twig',
        self::STATE_REQUEST_TRIP_OUTBOUND_PORTS => 'international_survey/trip/form-outbound-ports.html.twig',
        self::STATE_REQUEST_TRIP_OUTBOUND_CARGO_STATE => 'international_survey/trip/form-outbound-cargo-state.html.twig',
        self::STATE_REQUEST_TRIP_RETURN_PORTS => 'international_survey/trip/form-return-ports.html.twig',
        self::STATE_REQUEST_TRIP_RETURN_CARGO_STATE => 'international_survey/trip/form-return-cargo-state.html.twig',
        self::STATE_REQUEST_TRIP_DISTANCE => 'international_survey/trip/form-distance.html.twig',
        self::STATE_REQUEST_TRIP_COUNTRIES_TRANSITTED => 'international_survey/trip/form-countries-transitted.html.twig',

        self::STATE_CHANGE_TRIP_DATES => 'international_survey/trip/form-dates.html.twig',
        self::STATE_CHANGE_TRIP_OUTBOUND_PORTS => 'international_survey/trip/form-outbound-ports.html.twig',
        self::STATE_CHANGE_TRIP_OUTBOUND_CARGO_STATE => 'international_survey/trip/form-outbound-cargo-state.html.twig',
        self::STATE_CHANGE_TRIP_RETURN_PORTS => 'international_survey/trip/form-return-ports.html.twig',
        self::STATE_CHANGE_TRIP_RETURN_CARGO_STATE => 'international_survey/trip/form-return-cargo-state.html.twig',
        self::STATE_CHANGE_TRIP_DISTANCE => 'international_survey/trip/form-distance.html.twig',
        self::STATE_CHANGE_TRIP_COUNTRIES_TRANSITTED => 'international_survey/trip/form-countries-transitted.html.twig',
    ];

    /** @var Trip */
    private $subject;

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === Trip::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . Trip::class);
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

    public function isValidAlternativeStartState($state): bool
    {
        if (!$this->subject instanceof Trip) {
            return false;
        }

        $alternativeStartStates = [
            self::STATE_CHANGE_TRIP_DATES,
            self::STATE_CHANGE_TRIP_OUTBOUND_PORTS,
            self::STATE_CHANGE_TRIP_RETURN_PORTS,
            self::STATE_CHANGE_TRIP_DISTANCE,
            self::STATE_CHANGE_TRIP_COUNTRIES_TRANSITTED,
        ];

        return $this->subject->getId() ?
            in_array($state, $alternativeStartStates) :
            false;
    }
}