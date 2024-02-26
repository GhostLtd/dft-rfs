<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\InitialDetails\ContactDetailsType;
use App\Form\DomesticSurvey\InitialDetails\HireeDetailsType;
use App\Form\DomesticSurvey\InitialDetails\ScrappedDetailsType;
use App\Form\DomesticSurvey\InitialDetails\SoldDetailsType;
use App\Form\DomesticSurvey\InitialDetails\InPossessionOfVehicleType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardStateInterface;

class InitialDetailsState extends AbstractFormWizardState implements FormWizardStateInterface
{
    public const STATE_INTRODUCTION = 'introduction';
    public const STATE_REQUEST_CONTACT_DETAILS = 'contact-details';
    public const STATE_ASK_IN_POSSESSION = "in-possession" ;
    public const STATE_ASK_HIREE_DETAILS = 'hiree-details';
    public const STATE_ASK_SCRAPPED_DETAILS = 'scrapped-details';
    public const STATE_ASK_SOLD_DETAILS = 'sold-details';
    public const STATE_END = 'end';
    public const STATE_CHANGE_CONTACT_DETAILS = 'change-contact-details';

    private const array FORM_MAP = [
        self::STATE_REQUEST_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_CHANGE_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_ASK_IN_POSSESSION => InPossessionOfVehicleType::class,
        self::STATE_ASK_HIREE_DETAILS => HireeDetailsType::class,
        self::STATE_ASK_SOLD_DETAILS => SoldDetailsType::class,
        self::STATE_ASK_SCRAPPED_DETAILS => ScrappedDetailsType::class,
    ];

    private const array TEMPLATE_MAP = [
        self::STATE_INTRODUCTION => 'domestic_survey/initial_details/introduction.html.twig',
        self::STATE_REQUEST_CONTACT_DETAILS => 'domestic_survey/initial_details/form-contact-details.html.twig',
        self::STATE_CHANGE_CONTACT_DETAILS => 'domestic_survey/initial_details/form-contact-details.html.twig',
        self::STATE_ASK_IN_POSSESSION => 'domestic_survey/initial_details/form-in-possession.html.twig',
        self::STATE_ASK_HIREE_DETAILS => 'domestic_survey/initial_details/form-hiree-details.html.twig',
        self::STATE_ASK_SOLD_DETAILS => 'domestic_survey/initial_details/form-sold-details.html.twig',
        self::STATE_ASK_SCRAPPED_DETAILS => 'domestic_survey/initial_details/form-scrapped-details.html.twig',
    ];

    /** @var SurveyResponse */
    private $subject;

    #[\Override]
    public function getSubject()
    {
        return $this->subject;
    }

    #[\Override]
    public function setSubject($subject): self
    {
        if (!is_null($subject) && $subject::class !== SurveyResponse::class) {
            throw new \InvalidArgumentException("Got " . $subject::class . ", expected " . SurveyResponse::class);
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
        return 'domestic_survey/initial_details/form-step.html.twig';
    }

    #[\Override]
    public function isValidAlternativeStartState($state): bool
    {
        if ($this->subject->getId()) {
            return in_array($state, [
                self::STATE_CHANGE_CONTACT_DETAILS,
                self::STATE_ASK_IN_POSSESSION,
            ]);
        }
        return parent::isValidAlternativeStartState($state);
    }
}