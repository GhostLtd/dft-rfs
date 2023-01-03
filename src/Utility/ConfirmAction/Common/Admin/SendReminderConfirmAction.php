<?php

namespace App\Utility\ConfirmAction\Common\Admin;

use App\Entity\SurveyInterface;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\Reminder\ManualReminderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class SendReminderConfirmAction extends AbstractConfirmAction
{
    /** @var SurveyInterface */
    protected $subject;
    protected EntityManagerInterface $entityManager;
    protected ?string $passcode;
    protected ManualReminderHelper $reminderHelper;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, EntityManagerInterface $entityManager, ManualReminderHelper $reminderHelper)
    {
        parent::__construct($formFactory, $requestStack, $translator);
        $this->entityManager = $entityManager;
        $this->reminderHelper = $reminderHelper;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--primary'],
            ],
        ]);
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'admin.send-reminder';
    }

    public function doConfirmedAction($formData)
    {
        $this->reminderHelper->sendManualReminder($this->subject);
    }
}