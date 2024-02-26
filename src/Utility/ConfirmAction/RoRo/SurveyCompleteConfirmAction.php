<?php

namespace App\Utility\ConfirmAction\RoRo;

use App\Utility\ConfirmAction\RoRo\Admin\SurveyWorkflowConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class SurveyCompleteConfirmAction extends SurveyWorkflowConfirmAction
{
    public function __construct(WorkflowInterface $roroSurveyStateMachine, EntityManagerInterface $entityManager, FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator)
    {
        parent::__construct($roroSurveyStateMachine, $entityManager, $formFactory, $requestStack, $translator);
        $this->setTransition('complete');
    }

    #[\Override]
    public function getTranslationDomain(): ?string
    {
        return 'messages';
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'roro.survey.complete';
    }
}