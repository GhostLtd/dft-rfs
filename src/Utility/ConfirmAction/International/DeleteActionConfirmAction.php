<?php

namespace App\Utility\ConfirmAction\International;

use App\Entity\International\Action;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteActionConfirmAction extends AbstractConfirmAction
{
    /** @var Action */
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
        return 'international.action-delete';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
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