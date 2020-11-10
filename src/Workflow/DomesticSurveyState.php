<?php


namespace App\Workflow;


use App\Entity\DomesticSurvey;
use App\Entity\DomesticSurveyResponse;
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
        self::STATE_REQUEST_CONTACT_DETAILS => 'form-contact-details',
        self::STATE_CHANGE_CONTACT_DETAILS => 'form-contact-details',
        self::STATE_ASK_HIREE_DETAILS => 'form-hiree-details',
    ];

    private $state = self::STATE_INTRODUCTION;

    /** @var DomesticSurveyResponse */
    private $subject;

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
        return $this->subject;
    }

    public function setSubject($subject): self
    {
        if (!get_class($subject) === DomesticSurveyResponse::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . DomesticSurveyResponse::class);
        $this->subject = $subject;
        return $this;
    }

    public function isValidJumpInState($state)
    {
        return (in_array($state, $this->getValidJumpInStates()));
    }

    protected function getValidJumpInStates()
    {
        $states = [self::STATE_INTRODUCTION, self::STATE_REQUEST_CONTACT_DETAILS];
        if (!empty($this->subject->getContactName())) {
            $states[] = self::STATE_ASK_COMPLETABLE;
            $states[] = self::STATE_CHANGE_CONTACT_DETAILS;
        }
        if ($this->subject->getAbleToComplete()) {
            $states[] = self::STATE_ASK_ON_HIRE;
        } else {
            $states[] = self::STATE_ASK_REASON_CANT_COMPLETE;
        }
        if ($this->subject->getUnableToCompleteReason() === 'on-hire') $states[] = self::STATE_ASK_HIREE_DETAILS;
        return dump($states);
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