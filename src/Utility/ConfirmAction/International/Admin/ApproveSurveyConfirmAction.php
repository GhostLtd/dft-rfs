<?php


namespace App\Utility\ConfirmAction\International\Admin;


use App\Entity\International\Survey;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Workflow\WorkflowInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApproveSurveyConfirmAction extends AbstractConfirmAction
{
    /** @var Survey */
    protected $subject;
    private WorkflowInterface $internationalSurveyStateMachine;

    public function __construct(FormFactoryInterface $formFactory, FlashBagInterface $flashBag, TranslatorInterface $translator, WorkflowInterface $internationalSurveyStateMachine)
    {
        parent::__construct($formFactory, $flashBag, $translator);
        $this->internationalSurveyStateMachine = $internationalSurveyStateMachine;
    }

    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'international.approve-survey';
    }

    public function doConfirmedAction()
    {
        $this->internationalSurveyStateMachine->apply($this->getSubject(), 'approve');
    }
}