<?php

namespace App\Utility\ConfirmAction;

use App\Entity\Utility\MaintenanceWarning;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteMaintenanceWarningConfirmAction extends AbstractConfirmAction
{
    /** @var MaintenanceWarning */
    protected $subject;
    private EntityManagerInterface $entityManager;

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
            'start' => $this->subject->getStart()->getTimestamp(),
            'end' => $this->subject->getEnd()->getTimestamp(),
        ];
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'admin.maintenance-warning.delete';
    }

    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}