<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\Trip;
use App\Form\InternationalSurvey\Trip\ChangedBodyTypeType;
use App\Form\InternationalSurvey\Trip\CountriesTransittedType;
use App\Form\InternationalSurvey\Trip\DatesType;
use App\Form\InternationalSurvey\Trip\DistanceType;
use App\Form\InternationalSurvey\Trip\OriginAndDestinationType;
use App\Form\InternationalSurvey\Trip\OutboundPortsAndCargoStateType;
use App\Form\InternationalSurvey\Trip\ReturnPortsAndCargoStateType;
use App\Form\InternationalSurvey\Trip\SwappedTrailerType;
use App\Form\InternationalSurvey\Trip\VehicleWeightType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;
use InvalidArgumentException;

class TripState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_ORIGIN_AND_DESTINATION = 'origin-and-destination';
    public const STATE_DATES = 'dates';
    public const STATE_OUTBOUND_PORTS = 'outbound-ports';
    public const STATE_RETURN_PORTS = 'return-ports';
    public const STATE_CHANGED_BODY_TYPE = 'body-type';
    public const STATE_SWAPPED_TRAILER = 'swapped-trailer';
    public const STATE_NEW_VEHICLE_WEIGHTS = 'vehicle-weights';
    public const STATE_DISTANCE = 'distance';
    public const STATE_COUNTRIES_TRANSITTED = 'countries-transitted';

    public const STATE_SUMMARY = 'summary';

    private const array FORM_MAP = [
        self::STATE_ORIGIN_AND_DESTINATION => OriginAndDestinationType::class,
        self::STATE_DATES => DatesType::class,
        self::STATE_OUTBOUND_PORTS => OutboundPortsAndCargoStateType::class,
        self::STATE_RETURN_PORTS => ReturnPortsAndCargoStateType::class,
        self::STATE_CHANGED_BODY_TYPE=> ChangedBodyTypeType::class,
        self::STATE_SWAPPED_TRAILER => SwappedTrailerType::class,
        self::STATE_NEW_VEHICLE_WEIGHTS => VehicleWeightType::class,
        self::STATE_DISTANCE => DistanceType::class,
        self::STATE_COUNTRIES_TRANSITTED => CountriesTransittedType::class,
    ];

    private const array TEMPLATE_MAP = [
        self::STATE_ORIGIN_AND_DESTINATION => 'international_survey/trip/form-origin-and-destination.html.twig',
        self::STATE_DATES => 'international_survey/trip/form-dates.html.twig',
        self::STATE_OUTBOUND_PORTS => 'international_survey/trip/form-outbound-ports.html.twig',
        self::STATE_RETURN_PORTS => 'international_survey/trip/form-return-ports.html.twig',
        self::STATE_CHANGED_BODY_TYPE => 'international_survey/trip/form-changed-body-type.html.twig',
        self::STATE_SWAPPED_TRAILER => 'international_survey/trip/form-swapped-trailer.html.twig',
        self::STATE_NEW_VEHICLE_WEIGHTS => 'international_survey/trip/form-vehicle-weights.html.twig',
        self::STATE_DISTANCE => 'international_survey/trip/form-distance.html.twig',
        self::STATE_COUNTRIES_TRANSITTED => 'international_survey/trip/form-countries-transitted.html.twig',
    ];

    /** @var Trip */
    private $subject;

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if ($subject::class !== Trip::class) {
            throw new InvalidArgumentException("Got " . $subject::class . ", expected " . Trip::class);
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
        if (!$this->subject instanceof Trip) {
            return false;
        }

        $alternativeStartStates = [
            self::STATE_DATES,
            self::STATE_OUTBOUND_PORTS,
            self::STATE_RETURN_PORTS,
            self::STATE_SWAPPED_TRAILER,
            self::STATE_DISTANCE,
            self::STATE_COUNTRIES_TRANSITTED,
        ];

        return $this->subject->getId() ?
            in_array($state, $alternativeStartStates) :
            false;
    }
}