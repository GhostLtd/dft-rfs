<?php

namespace App\Utility\ConfirmAction\RoRo\Admin;

use App\Entity\RoRo\Survey;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
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

    public function setTransition(string $transition): self
    {
        $this->transition = $transition;
        return $this;
    }

    public function __construct(
        protected WorkflowInterface $roroSurveyStateMachine,
        protected EntityManagerInterface $entityManager,
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        TranslatorInterface $translator
    ) {
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
    public function doConfirmedAction($formData): void
    {
        $this->roroSurveyStateMachine->apply($this->getSubject(), $this->transition);
        $this->entityManager->flush();
    }

    public function getSurvey(): Survey
    {
        return $this->subject;
    }
}