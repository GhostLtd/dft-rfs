<?php

namespace App\Utility\ConfirmAction;

use App\Entity\Route\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteRouteConfirmAction extends AbstractConfirmAction
{
    /** @var Route */
    protected $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, protected EntityManagerInterface $entityManager)
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
            'uk_port_name' => $this->subject->getUkPort()->getName(),
            'uk_port_code' => $this->subject->getUkPort()->getCode(),
            'foreign_port_name' => $this->subject->getForeignPort()->getName(),
            'foreign_port_code' => $this->subject->getForeignPort()->getCode(),
        ];
    }

    #[\Override]
    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'route.delete';
    }

    #[\Override]
    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}