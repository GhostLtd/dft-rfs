<?php

namespace App\Utility\ConfirmAction\International\Admin;

use App\Entity\International\Survey;
use App\Form\Admin\InternationalSurvey\FinalDetailsType;
use App\Form\ConfirmActionType;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
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

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, private WorkflowInterface $internationalSurveyStateMachine, private EntityManagerInterface $entityManager)
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

        switch($formClass) {
            case ConfirmActionType::class:
                $data = null;
                $formOptions = $this->getFormOptions();
                break;
            case FinalDetailsType::class:
                $data = $this->getSurvey();
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

    public function getSurvey(): Survey
    {
        return $this->subject;
    }
}