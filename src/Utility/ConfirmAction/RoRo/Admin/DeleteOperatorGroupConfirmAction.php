<?php

namespace App\Utility\ConfirmAction\RoRo\Admin;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteOperatorGroupConfirmAction extends AbstractConfirmAction
{
    /** @var OperatorGroup */
    protected $subject;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        protected EntityManagerInterface $entityManager
    ) {
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
            'name' => $this->subject->getName(),
        ];
    }

    #[\Override]
    public function getTranslationDomain(): ?string
    {
        return 'messages';
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'admin.operator-groups.delete';
    }

    #[\Override]
    public function doConfirmedAction($formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}