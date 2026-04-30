<?php

namespace App\Utility\ConfirmAction\International;

use App\Entity\International\Vehicle;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteVehicleConfirmAction extends AbstractConfirmAction
{
    /** @var Vehicle */
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
    public function getTranslationKeyPrefix(): string
    {
        return 'international.vehicle-delete';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}