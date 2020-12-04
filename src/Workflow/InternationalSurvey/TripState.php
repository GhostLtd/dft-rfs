<?php

namespace App\Workflow\InternationalSurvey;

use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardInterface;
use InvalidArgumentException;

class TripState extends AbstractFormWizardState implements FormWizardInterface
{
    const STATE_REQUEST_TRIP_OUTBOUND_PORTS = 'outbound-ports';
    const STATE_REQUEST_TRIP_OUTBOUND_CARGO = 'outbound-cargo';
    const STATE_REQUEST_TRIP_RETURN_PORTS = 'return-ports';
    const STATE_REQUEST_TRIP_RETURN_CARGO = 'return-cargo';
    const STATE_REQUEST_TRIP_DISTANCE = 'distance';
    const STATE_REQUEST_TRIP_TRANSITTED_COUNTRIES = 'transitted-countries';

    const STATE_SUMMARY = 'summary';

    private const FORM_MAP = [

    ];

    private const TEMPLATE_MAP = [
        self::STATE_REQUEST_TRIP_OUTBOUND_PORTS => 'international_survey/trip/form-outbound-ports.html.twig',
        self::STATE_REQUEST_TRIP_OUTBOUND_CARGO => 'international_survey/trip/form-outbound-cargo.html.twig',
        self::STATE_REQUEST_TRIP_RETURN_PORTS => 'international_survey/trip/form-return-ports.html.twig',
        self::STATE_REQUEST_TRIP_RETURN_CARGO => 'international_survey/trip/form-return-cargo.html.twig',
        self::STATE_REQUEST_TRIP_DISTANCE => 'international_survey/trip/form-distance.html.twig',
        self::STATE_REQUEST_TRIP_TRANSITTED_COUNTRIES => 'international_survey/trip/form-transitted.html.twig',
    ];

    /** @var SurveyResponse */
    private $subject;

    public function getSubject()
    {
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === SurveyResponse::class) {
            throw new InvalidArgumentException("Got " . get_class($subject) . ", expected " . SurveyResponse::class);
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
        ];

        return $this->subject->getId() ?
            in_array($state, $alternativeStartStates) :
            false;
    }
}