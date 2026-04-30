<?php

namespace App\Utility\ConfirmAction\RoRo\Admin;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteOperatorGroupConfirmAction extends AbstractConfirmAction
{
    /** @var OperatorGroup */
    protected mixed $subject;

    public function __construct(
        FormFactoryInterface $formFactory,
        RequestStack $requestStack,
        protected EntityManagerInterface $entityManager
    ) {
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
    public function doConfirmedAction(mixed $formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}