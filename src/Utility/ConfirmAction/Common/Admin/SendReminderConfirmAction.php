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
    protected ?string $passcode = null;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, protected EntityManagerInterface $entityManager, protected ManualReminderHelper $reminderHelper)
    {
        parent::__construct($formFactory, $requestStack, $translator);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--primary'],
            ],
        ]);
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'admin.send-reminder';
    }

    #[\Override]
    public function doConfirmedAction($formData)
    {
        $this->reminderHelper->sendManualReminder($this->subject);
    }
}