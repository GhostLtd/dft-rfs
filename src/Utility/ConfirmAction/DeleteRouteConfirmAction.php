<?php

namespace App\Utility\ConfirmAction;

use App\Entity\Route\Route;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteRouteConfirmAction extends AbstractConfirmAction
{
    /** @var Route */
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
    public function doConfirmedAction(mixed $formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}