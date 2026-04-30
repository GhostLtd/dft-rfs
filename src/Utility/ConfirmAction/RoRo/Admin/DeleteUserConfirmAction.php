<?php

namespace App\Utility\ConfirmAction\RoRo\Admin;

use App\Entity\RoRoUser;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteUserConfirmAction extends AbstractConfirmAction
{
    /** @var RoRoUser */
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
            'username' => $this->subject->getEmail(),
            'operator_name' => $this->subject->getOperator()->getName(),
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
        return 'operator.user.delete';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}