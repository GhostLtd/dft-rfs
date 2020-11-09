<?php


namespace App\Workflow;


use App\Entity\DomesticSurvey;

class DomesticSurveyState
{
    const STATE_PRE_SURVEY_INTRODUCTION = 'introduction';
    const STATE_PRE_SURVEY_REQUEST_CONTACT_DETAILS = 'contact-details';
    const STATE_PRE_SURVEY_ASK_COMPLETABLE = 'can-complete';
    const STATE_PRE_SURVEY_ASK_ON_HIRE = 'on-hire';
    const STATE_PRE_SURVEY_ASK_REASON_CANT_COMPLETE = "reason-non-completion" ;
    const STATE_PRE_SURVEY_ASK_HIREE_DETAILS = 'hiree-details';
    const STATE_PRE_SURVEY_SUMMARY = 'summary';
    const STATE_PRE_SURVEY_CHANGE_CONTACT_DETAILS = 'change-contact-details';


    private $state = self::STATE_PRE_SURVEY_INTRODUCTION;

    private $visitedStates = [];

    /** @var DomesticSurvey */
    private $survey;

    public function __construct()
    {
        $this->survey = new DomesticSurvey();
    }

    public function setVisitedState($state)
    {
        $this->visitedStates[] = $state;
        $this->visitedStates = array_unique($this->visitedStates);
    }

    /**
     * @return array
     */
    public function getVisitedStates(): array
    {
        return $this->visitedStates;
    }

    public function isVisitedState($state)
    {
        return array_search($state, $this->visitedStates) !== false;
    }

    /**
     * @return DomesticSurvey
     */
    public function getSurvey(): DomesticSurvey
    {
        return $this->survey;
    }

    /**
     * @param DomesticSurvey $survey
     */
    public function setSurvey(DomesticSurvey $survey): self
    {
        $this->survey = $survey;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state): self
    {
        $this->state = $state;
        return $this;
    }
}