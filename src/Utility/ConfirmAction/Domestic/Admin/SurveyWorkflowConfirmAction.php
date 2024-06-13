<?php

namespace App\Utility\ConfirmAction\Domestic\Admin;

use App\Entity\Domestic\Survey;
use App\Form\Admin\DomesticSurvey\ConfirmApprovedActionType;
use App\Form\Admin\DomesticSurvey\FinalDetailsType;
use App\Form\ConfirmActionType;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\ConfirmAction\ActionFailedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SurveyWorkflowConfirmAction extends AbstractConfirmAction
{
    protected string $transition;

    /** @var Survey */
    protected $subject;

    public function getTransition(): string
    {
        return $this->transition;
    }

    public function setTransition(string $transition): static
    {
        $this->transition = $transition;
        return $this;
    }

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, private WorkflowInterface $domesticSurveyStateMachine, private EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack, $translator);
    }

    #[\Override]
    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    #[\Override]
    function getTranslationParameters(): array
    {
        return [
            'transitionTitleUcfirst' => ucfirst(str_replace('_', '-', $this->transition)),
            'transitionTitle' => str_replace('_', '-', $this->transition),
        ];
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return "common.survey.workflow-transition";
    }

    #[\Override]
    public function getFormClass(): string
    {
        $survey = $this->getSurvey();

        if ($this->getTransition() === 'approve') {
            if ($survey->shouldAskWhyUnfilled()) {
                return ConfirmApprovedActionType::class;
            }

            if ($survey->shouldAskWhyNoJourneys() && !$survey->getResponse()->getReasonForEmptySurvey()) {
                return FinalDetailsType::class;
            }
        }

        return ConfirmActionType::class;
    }

    #[\Override]
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
            default:
                throw new \InvalidArgumentException('Invalid formClass');
        }

        return $this->formFactory->create($formClass, $data, $formOptions);
    }

    #[\Override]
    public function doConfirmedAction($formData): void
    {
        $survey = $this->getSurvey();

        try {
            $this->domesticSurveyStateMachine->apply($survey, $this->transition);
            $this->entityManager->flush();
        }
        catch(NotEnabledTransitionException) {
            throw new ActionFailedException();
        }
    }

    public function getSurvey(): Survey
    {
        return $this->subject;
    }
}
