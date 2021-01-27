<?php


namespace App\Utility\ConfirmAction\International;


use App\Entity\International\Trip;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use App\Utility\International\DeleteHelper;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteTripConfirmAction extends AbstractConfirmAction
{
    /** @var Trip */
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

    public function getTranslationKeyPrefix(): string
    {
        return 'international.trip-delete';
    }

    public function doConfirmedAction()
    {
        $this->deleteHelper->deleteTrip($this->subject);
    }
}