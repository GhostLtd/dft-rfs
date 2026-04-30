<?php

namespace App\Utility\ConfirmAction\Domestic\Admin;

use App\Entity\Domestic\DaySummary;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\Domestic\DeleteHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteDaySummaryConfirmAction extends AbstractConfirmAction
{
    /** @var DaySummary */
    protected mixed $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, private DeleteHelper $deleteHelper)
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
            'dayNumber' => $this->subject->getDay()->getNumber(),
        ];
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'domestic.day-summary-delete';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        $this->deleteHelper->deleteDaySummary($this->subject);
    }
}