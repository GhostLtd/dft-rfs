<?php

namespace App\Utility\ConfirmAction;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteEmailBlacklistConfirmAction extends AbstractConfirmAction
{
    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, private EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack, $translator);
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
    public function doConfirmedAction($formData = null): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }

    #[\Override]
    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => ['attr' => ['class' => 'govuk-button--warning']],
        ]);
    }

    #[\Override]
    public function getExtraViewData(): array
    {
        return [
            'email' => $this->subject->getEmail(),
        ];
    }
}
