<?php

namespace App\Utility\ConfirmAction\International;

use App\Entity\International\Vehicle;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteVehicleConfirmAction extends AbstractConfirmAction
{
    /** @var Vehicle */
    protected $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, private EntityManagerInterface $entityManager)
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
    public function getTranslationKeyPrefix(): string
    {
        return 'international.vehicle-delete';
    }

    #[\Override]
    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}