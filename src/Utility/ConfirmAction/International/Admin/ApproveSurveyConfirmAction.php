<?php

namespace App\Utility\ConfirmAction\International\Admin;

use App\Entity\International\Survey;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;

class ApproveSurveyConfirmAction extends AbstractConfirmAction
{
    /** @var Survey */
    protected mixed $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, private WorkflowInterface $internationalSurveyStateMachine)
    {
        parent::__construct($formFactory, $requestStack);
    }

    #[\Override]
    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'international.approve-survey';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        $this->internationalSurveyStateMachine->apply($this->getSubject(), 'approve');
    }
}