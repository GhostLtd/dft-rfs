<?php

namespace App\Utility\ConfirmAction\PreEnquiry\Admin;

use App\Entity\PreEnquiry\PreEnquiry;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;

class PreEnquiryWorkflowConfirmAction extends AbstractConfirmAction
{
    protected string $transition;

    /** @var PreEnquiry */
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

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, private WorkflowInterface $preEnquiryStateMachine, private EntityManagerInterface $entityManager)
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
        $this->preEnquiryStateMachine->apply($this->getSubject(), $this->transition);
        $this->entityManager->flush();
    }
}