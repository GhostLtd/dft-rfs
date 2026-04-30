<?php

namespace App\Utility\ConfirmAction\Common\Admin;

use App\Entity\Domestic\SurveyNote as DomesticSurveyNote;
use App\Entity\International\SurveyNote as InternationalSurveyNote;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class DeleteSurveyNoteConfirmAction extends AbstractConfirmAction
{
    /** @var DomesticSurveyNote | InternationalSurveyNote */
    protected mixed $subject;
    protected array $extraViewData;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, protected EntityManagerInterface $entityManager)
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
        return 'admin.delete-survey-note';
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        $this->entityManager->remove($this->subject);
        $this->entityManager->flush();
    }
}