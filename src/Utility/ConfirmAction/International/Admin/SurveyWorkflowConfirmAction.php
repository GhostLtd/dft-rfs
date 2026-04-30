<?php

namespace App\Utility\ConfirmAction\International\Admin;

use App\Entity\International\Survey;
use App\Form\Admin\InternationalSurvey\FinalDetailsType;
use Ghost\GovUkCoreBundle\Form\ConfirmActionType;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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

    public function setTransition(string $transition): self
    {
        $this->transition = $transition;
        return $this;
    }

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, private WorkflowInterface $internationalSurveyStateMachine, private EntityManagerInterface $entityManager)
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
    public function doConfirmedAction(mixed $formData): void
    {
        $this->internationalSurveyStateMachine->apply($this->getSubject(), $this->transition);
        $this->entityManager->flush();
    }

    #[\Override]
    public function getFormClass(): string
    {
        $survey = $this->getSurvey();

        if ($this->getTransition() === 'approve') {
            if ($survey->shouldAskWhyEmptySurvey() && !$survey->getReasonForEmptySurvey()) {
                return FinalDetailsType::class;
            }
        }

        return ConfirmActionType::class;
    }

    #[\Override]
    public function getForm(): FormInterface
    {
        $formClass = $this->getFormClass();

        $data = match($formClass) {
            FinalDetailsType::class => $this->getSurvey(),
            ConfirmActionType::class => null,
            default => throw new \InvalidArgumentException('Invalid formClass'),
        };

        return $this->formFactory->create($formClass, $data, $this->getFormOptions());
    }

    public function getSurvey(): Survey
    {
        return $this->subject;
    }
}