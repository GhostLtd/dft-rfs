<?php

namespace App\Utility\ConfirmAction\Common\Admin;

use App\Entity\Domestic\SurveyNote as DomesticSurveyNote;
use App\Entity\International\SurveyNote as InternationalSurveyNote;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteSurveyNoteConfirmAction extends AbstractConfirmAction
{
    /** @var DomesticSurveyNote | InternationalSurveyNote */
    protected $subject;
    protected EntityManagerInterface $entityManager;
    protected array $extraViewData;

    public function __construct(FormFactoryInterface $formFactory, FlashBagInterface $flashBag, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $flashBag, $translator);
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
        return 'admin.delete-survey-note';
    }

    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }

    public function getExtraViewData(): array
    {
        return $this->extraViewData;
    }

    public function setExtraViewData(array $extraViewData): DeleteSurveyNoteConfirmAction
    {
        $this->extraViewData = $extraViewData;
        return $this;
    }
}