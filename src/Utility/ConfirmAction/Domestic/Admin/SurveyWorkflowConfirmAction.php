<?php

namespace App\Utility\ConfirmAction\Domestic\Admin;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Form\Admin\DomesticSurvey\ConfirmApprovedActionType;
use App\Form\Admin\DomesticSurvey\FinalDetailsType;
use App\Form\ConfirmActionType;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SurveyWorkflowConfirmAction extends AbstractConfirmAction
{
    protected string $transition;

    /** @var Survey */
    protected $subject;
    private WorkflowInterface $domesticSurveyStateMachine;
    private EntityManagerInterface $entityManager;

    public function getTransition(): string
    {
        return $this->transition;
    }

    public function setTransition(string $transition): self
    {
        $this->transition = $transition;
        return $this;
    }


    public function __construct(FormFactoryInterface $formFactory, FlashBagInterface $flashBag, TranslatorInterface $translator, WorkflowInterface $domesticSurveyStateMachine, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $flashBag, $translator);
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
        $this->entityManager = $entityManager;
    }

    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    function getTranslationParameters(): array
    {
        return [
            'transitionTitleUcfirst' => ucfirst(str_replace('_', '-', $this->transition)),
            'transitionTitle' => str_replace('_', '-', $this->transition),
        ];
    }

    public function getTranslationKeyPrefix(): string
    {
        return "common.survey.workflow-transition";
    }

    public function getFormClass(): string
    {
        if ($this->getTransition() === 'approve') {
            if ($this->shouldAskWhyUnfilled()) {
                return ConfirmApprovedActionType::class;
            }

            if ($this->shouldAskWhyNoJourneys()) {
                return FinalDetailsType::class;
            }
        }

        return ConfirmActionType::class;
    }

    public function getForm(): FormInterface
    {
        $formClass = $this->getFormClass();
        $survey = $this->getSurvey();

        switch($formClass) {
            case ConfirmApprovedActionType::class:
                $data = $survey;
                $formOptions = $this->getFormOptions();
                break;
            case ConfirmActionType::class:
                $data = null;
                $formOptions = $this->getFormOptions();
                break;
            case FinalDetailsType::class:
                $data = $survey->getResponse();
                $formOptions = [
                    'submit_name' => 'confirm',
                    'submit_label' => $this->getTranslationKeyPrefix().".confirm.label",
                    'label_translation_domain' => $this->getTranslationDomain(),
                    'label_translation_parameters' => $this->getTranslationParameters(),
                ];
                break;
        }

        return $this->formFactory->create($formClass, $data, $formOptions);
    }

    public function doConfirmedAction($formData)
    {
        $survey = $this->getSurvey();
        $response = $survey->getResponse();

        if ($this->getTransition() === 'approve') {
            if (!$this->shouldAskWhyUnfilled()) {
                $survey->setReasonForUnfilledSurvey(null);
            }

            if (!$this->shouldAskWhyNoJourneys()) {
                $response->setReasonForEmptySurvey(null);
            }
        }

        $this->domesticSurveyStateMachine->apply($survey, $this->transition);
        $this->entityManager->flush();
    }

    public function getSurvey(): Survey
    {
        return $this->subject;
    }

    protected function shouldAskWhyUnfilled(): bool
    {
        $survey = $this->getSurvey();
        $response = $survey->getResponse();

        $isInPossession = $response && $response->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_YES;

        return !$survey->isInitialDetailsComplete() ||
               ($isInPossession && !$survey->isBusinessAndVehicleDetailsComplete());
    }

    protected function shouldAskWhyNoJourneys(): bool
    {
        $survey = $this->getSurvey();
        $response = $survey->getResponse();

        return $survey->isBusinessAndVehicleDetailsComplete() &&
            $response->getIsInPossessionOfVehicle() === SurveyResponse::IN_POSSESSION_YES &&
            !$response->hasJourneys();
    }
}