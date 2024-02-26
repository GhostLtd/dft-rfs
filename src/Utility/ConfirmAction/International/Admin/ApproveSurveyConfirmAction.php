<?php

namespace App\Utility\ConfirmAction\International\Admin;

use App\Entity\International\Survey;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApproveSurveyConfirmAction extends AbstractConfirmAction
{
    /** @var Survey */
    protected $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, private WorkflowInterface $internationalSurveyStateMachine)
    {
        parent::__construct($formFactory, $requestStack, $translator);
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
    public function doConfirmedAction($formData)
    {
        $this->internationalSurveyStateMachine->apply($this->getSubject(), 'approve');
    }
}