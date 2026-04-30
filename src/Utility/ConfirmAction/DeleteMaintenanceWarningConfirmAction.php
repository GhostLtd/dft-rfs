<?php

namespace App\Utility\ConfirmAction;

use App\Entity\Utility\MaintenanceWarning;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteMaintenanceWarningConfirmAction extends AbstractConfirmAction
{
    /** @var MaintenanceWarning */
    protected mixed $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, private EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
    }

    #[\Override]
    public function getFormOptions(): array
    {
        $options = parent::getFormOptions();
        $options['confirm_button_options']['attr'] = ['class' => 'govuk-button--warning'];
        return $options;
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
    public function doConfirmedAction(mixed $formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}