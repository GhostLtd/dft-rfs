<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\InitialDetails\AbleToCompleteType;
use App\Form\DomesticSurvey\InitialDetails\ContactDetailsType;
use App\Form\DomesticSurvey\InitialDetails\HireeDetailsType;
use App\Form\DomesticSurvey\InitialDetails\ScrappedDetailsType;
use App\Form\DomesticSurvey\InitialDetails\SoldDetailsType;
use App\Form\DomesticSurvey\InitialDetails\UnableToCompleteOnHireType;
use App\Form\DomesticSurvey\InitialDetails\UnableToCompleteType;
use App\Workflow\FormWizardInterface;

class InitialDetailsState implements FormWizardInterface
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_REQUEST_CONTACT_DETAILS = 'contact-details';
    const STATE_ASK_COMPLETABLE = 'can-complete';
    const STATE_ASK_ON_HIRE = 'on-hire';
    const STATE_ASK_REASON_CANT_COMPLETE = "reason-non-completion" ;
    const STATE_ASK_HIREE_DETAILS = 'hiree-details';
    const STATE_ASK_SCRAPPED_DETAILS = 'scrapped-details';
    const STATE_ASK_SOLD_DETAILS = 'sold-details';
    const STATE_SUMMARY = 'summary';
    const STATE_CHANGE_CONTACT_DETAILS = 'change-contact-details';

    private const FORM_MAP = [
        self::STATE_REQUEST_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_CHANGE_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_ASK_COMPLETABLE => AbleToCompleteType::class,
        self::STATE_ASK_ON_HIRE => UnableToCompleteOnHireType::class,
        self::STATE_ASK_REASON_CANT_COMPLETE => UnableToCompleteType::class,
        self::STATE_ASK_HIREE_DETAILS => HireeDetailsType::class,
        self::STATE_ASK_SOLD_DETAILS => SoldDetailsType::class,
        self::STATE_ASK_SCRAPPED_DETAILS => ScrappedDetailsType::class,
    ];

    private const TEMPLATE_MAP = [
        self::STATE_INTRODUCTION => 'domestic_survey/initial_details/introduction.html.twig',
        self::STATE_REQUEST_CONTACT_DETAILS => 'domestic_survey/initial_details/form-contact-details.html.twig',
        self::STATE_CHANGE_CONTACT_DETAILS => 'domestic_survey/initial_details/form-contact-details.html.twig',
        self::STATE_ASK_HIREE_DETAILS => 'domestic_survey/initial_details/form-hiree-details.html.twig',
        self::STATE_ASK_SOLD_DETAILS => 'domestic_survey/initial_details/form-sold-details.html.twig',
    ];

    private $state = self::STATE_INTRODUCTION;

    /** @var SurveyResponse */
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
        if (!get_class($subject) === SurveyResponse::class) throw new \InvalidArgumentException("Got " . get_class($subject) . ", expected " . SurveyResponse::class);
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

    public function getDefaultTemplate()
    {
        return 'domestic_survey/initial_details/form-step.html.twig';
    }
}