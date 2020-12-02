<?php


namespace App\Workflow\DomesticSurvey;


use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\InitialDetails\ContactDetailsType;
use App\Form\DomesticSurvey\InitialDetails\HireeDetailsType;
use App\Form\DomesticSurvey\InitialDetails\ScrappedDetailsType;
use App\Form\DomesticSurvey\InitialDetails\SoldDetailsType;
use App\Form\DomesticSurvey\InitialDetails\InPossessionOfVehicleType;
use App\Workflow\AbstractFormWizardState;
use App\Workflow\FormWizardInterface;

class InitialDetailsState extends AbstractFormWizardState implements FormWizardInterface
{
    const STATE_INTRODUCTION = 'introduction';
    const STATE_REQUEST_CONTACT_DETAILS = 'contact-details';
    const STATE_ASK_IN_POSSESSION = "in-possession" ;
    const STATE_ASK_HIREE_DETAILS = 'hiree-details';
    const STATE_ASK_SCRAPPED_DETAILS = 'scrapped-details';
    const STATE_ASK_SOLD_DETAILS = 'sold-details';
    const STATE_SUMMARY = 'summary';
    const STATE_CHANGE_CONTACT_DETAILS = 'change-contact-details';

    private const FORM_MAP = [
        self::STATE_REQUEST_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_CHANGE_CONTACT_DETAILS => ContactDetailsType::class,
        self::STATE_ASK_IN_POSSESSION => InPossessionOfVehicleType::class,
        self::STATE_ASK_HIREE_DETAILS => HireeDetailsType::class,
        self::STATE_ASK_SOLD_DETAILS => SoldDetailsType::class,
        self::STATE_ASK_SCRAPPED_DETAILS => ScrappedDetailsType::class,
    ];

    private const TEMPLATE_MAP = [
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