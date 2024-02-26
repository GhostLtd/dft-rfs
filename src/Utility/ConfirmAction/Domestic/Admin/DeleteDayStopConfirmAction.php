<?php

namespace App\Utility\ConfirmAction\Domestic\Admin;

use App\Entity\Domestic\DayStop;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\Domestic\DeleteHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteDayStopConfirmAction extends AbstractConfirmAction
{
    /** @var DayStop */
    protected $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, private DeleteHelper $deleteHelper)
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
            'dayNumber' => $this->subject->getDay()->getNumber(),
            'stageNumber' => $this->subject->getNumber(),
        ];
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return 'domestic.day-stop-delete';
    }

    #[\Override]
    public function doConfirmedAction($formData)
    {
        $this->deleteHelper->deleteDayStop($this->subject);
    }
}