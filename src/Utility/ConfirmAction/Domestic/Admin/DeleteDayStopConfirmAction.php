<?php

namespace App\Utility\ConfirmAction\Domestic\Admin;

use App\Entity\Domestic\DayStop;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\Domestic\DeleteHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteDayStopConfirmAction extends AbstractConfirmAction
{
    /** @var DayStop */
    protected $subject;
    private DeleteHelper $deleteHelper;

    public function __construct(FormFactoryInterface $formFactory, FlashBagInterface $flashBag, TranslatorInterface $translator, DeleteHelper $deleteHelper)
    {
        parent::__construct($formFactory, $flashBag, $translator);
        $this->deleteHelper = $deleteHelper;
    }

    public function getFormOptions(): array
    {
        return array_merge(parent::getFormOptions(), [
            'confirm_button_options' => [
                'attr' => ['class' => 'govuk-button--warning'],
            ],
        ]);
    }

    public function getTranslationParameters(): array
    {
        return [
            'dayNumber' => $this->subject->getDay()->getNumber(),
            'stageNumber' => $this->subject->getNumber(),
        ];
    }

    public function getTranslationKeyPrefix(): string
    {
        return 'domestic.day-stop-delete';
    }

    public function doConfirmedAction()
    {
        $this->deleteHelper->deleteDayStop($this->subject);
    }
}