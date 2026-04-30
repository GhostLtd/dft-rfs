<?php

namespace App\Utility\ConfirmAction;

use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteNotificationInterceptionConfirmAction extends AbstractConfirmAction
{
    protected mixed $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, protected EntityManagerInterface $entityManager)
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
    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'notification-interception.delete';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}