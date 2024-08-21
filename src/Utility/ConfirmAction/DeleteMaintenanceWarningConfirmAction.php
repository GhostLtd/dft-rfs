<?php

namespace App\Utility\ConfirmAction;

use App\Entity\Utility\MaintenanceWarning;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteMaintenanceWarningConfirmAction extends AbstractConfirmAction
{
    /** @var MaintenanceWarning */
    protected $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, private EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack, $translator);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
    }

    #[\Override]
    public function getTranslationParameters(): array
    {
        return [
            'start' => $this->subject->getStart()->getTimestamp(),
            'end' => $this->subject->getEnd()->getTimestamp(),
        ];
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'admin.maintenance-warning.delete';
    }

    #[\Override]
    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}