<?php

namespace App\Utility\ConfirmAction\International;

use App\Entity\International\Action;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteActionConfirmAction extends AbstractConfirmAction
{
    /** @var Action */
    protected $subject;
    private EntityManagerInterface $entityManager;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack, $translator);
        $this->entityManager = $entityManager;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'international.action-delete';
    }

    public function doConfirmedAction($formData)
    {
        $trip = $this->subject->getTrip();
        $trip->removeAction($this->subject);
        foreach($this->subject->getUnloadingActions() as $unloadingAction) {
            $trip->removeAction($unloadingAction);
        }

        $trip->renumberActions();

        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}