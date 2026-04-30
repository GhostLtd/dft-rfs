<?php

namespace App\Utility\ConfirmAction\Domestic\Admin;

use App\Entity\Domestic\Survey;
use App\Form\Admin\DomesticSurvey\ConfirmApprovedActionType;
use App\Form\Admin\DomesticSurvey\FinalDetailsType;
use Ghost\GovUkCoreBundle\Form\ConfirmActionType;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\ActionFailedException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\Exception\NotEnabledTransitionException;
use Symfony\Component\Workflow\WorkflowInterface;

class SurveyWorkflowConfirmAction extends AbstractConfirmAction
{
    protected string $transition;

    /** @var Survey */
    protected mixed $subject;

    public function getTransition(): string
    {
        return $this->transition;
    }

    public function setTransition(string $transition): static
    {
        $this->transition = $transition;
        return $this;
    }

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, private WorkflowInterface $domesticSurveyStateMachine, private EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
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

        $data = match($formClass) {
            ConfirmApprovedActionType::class => $survey,
            FinalDetailsType::class => $survey->getResponse(),
            ConfirmActionType::class => null,
            default => throw new \InvalidArgumentException('Invalid formClass'),
        };

        return $this->formFactory->create($formClass, $data, $this->getFormOptions());
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
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
