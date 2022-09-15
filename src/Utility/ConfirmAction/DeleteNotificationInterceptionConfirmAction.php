<?php

namespace App\Utility\ConfirmAction;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteNotificationInterceptionConfirmAction extends AbstractConfirmAction
{
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

    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'notification-interception.delete';
    }

    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}