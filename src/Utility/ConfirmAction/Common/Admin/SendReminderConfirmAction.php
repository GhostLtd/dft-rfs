<?php

namespace App\Utility\ConfirmAction\Common\Admin;

use App\Entity\SurveyManualReminderInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\ActionFailedException;
use App\Utility\Reminder\ManualReminderHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class SendReminderConfirmAction extends AbstractConfirmAction
{
    /** @var SurveyManualReminderInterface */
    protected mixed $subject;
    protected ?string $passcode = null;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, protected EntityManagerInterface $entityManager, protected ManualReminderHelper $reminderHelper)
    {
        parent::__construct($formFactory, $requestStack);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        $options = parent::getFormOptions();
        $options['confirm_button_options']['attr'] = ['class' => 'govuk-button--primary'];
        return $options;
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'admin.send-reminder';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        if (!$this->reminderHelper->sendManualReminder($this->subject)) {
            throw new ActionFailedException();
        }
    }
}