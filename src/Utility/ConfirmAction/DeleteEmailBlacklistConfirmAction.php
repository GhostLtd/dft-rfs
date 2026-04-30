<?php

namespace App\Utility\ConfirmAction;

use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteEmailBlacklistConfirmAction extends AbstractConfirmAction
{
    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, private EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
    }

    #[\Override]
    public function getTranslationDomain(): string
    {
        return 'admin';
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'email-blacklist.delete';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData = null): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }

    #[\Override]
    public function getFormOptions(): array
    {
        $options = parent::getFormOptions();
        $options['confirm_button_options']['attr'] = ['class' => 'govuk-button--warning'];
        return $options;
    }

    #[\Override]
    public function getExtraViewData(): array
    {
        return [
            'email' => $this->subject->getEmail(),
        ];
    }
}
