<?php

namespace App\Utility\ConfirmAction\Domestic\Admin;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\NotificationInterception;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\Domestic\DeleteHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteNotificationInterceptionConfirmAction extends AbstractConfirmAction
{
    /** @var NotificationInterception */
    protected $subject;
    protected EntityManagerInterface $entityManager;

    public function __construct(FormFactoryInterface $formFactory, FlashBagInterface $flashBag, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $flashBag, $translator);
        $this->entityManager = $entityManager;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
    }

    public function getTranslationParameters(): array
    {
        return [
        ];
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'admin.domestic.notification-interception.delete';
    }

    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}