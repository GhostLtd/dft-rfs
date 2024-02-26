<?php

namespace App\Utility\ConfirmAction\PreEnquiry\Admin;

use App\Entity\PreEnquiry\PreEnquiry;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PreEnquiryWorkflowConfirmAction extends AbstractConfirmAction
{
    protected string $transition;

    /** @var PreEnquiry */
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

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, private WorkflowInterface $preEnquiryStateMachine, private EntityManagerInterface $entityManager)
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
    public function doConfirmedAction($formData)
    {
        $this->preEnquiryStateMachine->apply($this->getSubject(), $this->transition);
        $this->entityManager->flush();
    }
}