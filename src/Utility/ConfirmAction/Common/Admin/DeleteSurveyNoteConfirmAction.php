<?php

namespace App\Utility\ConfirmAction\Common\Admin;

use App\Entity\Domestic\SurveyNote as DomesticSurveyNote;
use App\Entity\International\SurveyNote as InternationalSurveyNote;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeleteSurveyNoteConfirmAction extends AbstractConfirmAction
{
    /** @var DomesticSurveyNote | InternationalSurveyNote */
    protected $subject;
    protected array $extraViewData;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, TranslatorInterface $translator, protected EntityManagerInterface $entityManager)
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
        return 'admin.delete-survey-note';
    }

    #[\Override]
    public function doConfirmedAction($formData)
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}