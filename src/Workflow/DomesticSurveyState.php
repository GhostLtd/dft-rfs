<?php


namespace App\Workflow;


use App\Entity\DomesticSurvey;
use App\Form\Domestic\CompletableStatusType;
use App\Form\Domestic\ContactDetailsType;
use App\Form\Domestic\HireeDetailsType;
use App\Form\Domestic\OnHireStatusType;
use App\Form\Domestic\ReasonCantCompleteType;

class DomesticSurveyState implements FormWizardInterface
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_REQUEST_CONTACT_DETAILS = 'contact-details';
    const STATE_ASK_COMPLETABLE = 'can-complete';
    const STATE_ASK_ON_HIRE = 'on-hire';
    const STATE_ASK_REASON_CANT_COMPLETE = "reason-non-completion" ;
    const STATE_ASK_HIREE_DETAILS = 'hiree-details';
    const STATE_SUMMARY = 'summary';
    const STATE_CHANGE_CONTACT_DETAILS = 'change-contact-details';

    private const FORM_MAP = [
        self::STATE_REQUEST_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_CHANGE_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_ASK_COMPLETABLE => CompletableStatusType::class,
        self::STATE_ASK_ON_HIRE => OnHireStatusType::class,
        self::STATE_ASK_REASON_CANT_COMPLETE => ReasonCantCompleteType::class,
        self::STATE_ASK_HIREE_DETAILS => HireeDetailsType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_INTRODUCTION => 'introduction',
        self::STATE_SUMMARY => 'summary',
        self::STATE_REQUEST_CONTACT_DETAILS => 'form-contact-details',
        self::STATE_CHANGE_CONTACT_DETAILS => 'form-contact-details',
        self::STATE_ASK_HIREE_DETAILS => 'form-hiree-details',
    ];

    private $state = self::STATE_INTRODUCTION;

    /** @var DomesticSurvey */
    private $survey;

    public function __construct()
    {
        $this->survey = new DomesticSurvey();
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

    public function getSubject()
    {
        return $this->survey;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === DomesticSurvey::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . DomesticSurvey::class);
        $this->survey = $subject;
        return $this;
    }

    public function isValidJumpInState($state)
    {
        return (in_array($state, $this->getValidJumpInStates()));
    }

    protected function getValidJumpInStates()
    {
        $states = [self::STATE_INTRODUCTION, self::STATE_REQUEST_CONTACT_DETAILS];
        if (!empty($this->survey->getSurveyResponse()->getContactName())) {
            $states[] = self::STATE_ASK_COMPLETABLE;
            $states[] = self::STATE_CHANGE_CONTACT_DETAILS;
        }
        if ($this->survey->getSurveyResponse()->getAbleToComplete()) {
            $states[] = self::STATE_ASK_ON_HIRE;
        } else {
            $states[] = self::STATE_ASK_REASON_CANT_COMPLETE;
        }
        if ($this->survey->getSurveyResponse()->getUnableToCompleteReason() === 'on-hire') $states[] = self::STATE_ASK_HIREE_DETAILS;
        return $states;
    }

    public function getStateFormMap()
    {
        return self::FORM_MAP;
    }

    public function getStateTemplateMap()
    {
        return self::TEMPLATE_MAP;
    }
}