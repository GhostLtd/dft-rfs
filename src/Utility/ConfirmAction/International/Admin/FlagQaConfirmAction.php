<?php

namespace App\Utility\ConfirmAction\International\Admin;

use App\Entity\International\Survey;
use App\Entity\HaulageSurveyInterface;
use App\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FlagQaConfirmAction extends AbstractConfirmAction
{
    protected string $transition;

    /** @var Survey */
    protected $subject;
    private EntityManagerInterface $entityManager;


    public function __construct(FormFactoryInterface $formFactory, FlashBagInterface $flashBag, TranslatorInterface $translator, EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $flashBag, $translator);
        $this->entityManager = $entityManager;
    }

    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    function getTranslationParameters(): array
    {
        return [];
    }

    public function getTranslationKeyPrefix(): string
    {
        return "common.survey.flag-qa";
    }

    public function doConfirmedAction($formData)
    {
        $survey = $this->getSubject();

        if ($survey instanceof HaulageSurveyInterface) {
            $survey->setQualityAssured(true);
            $this->entityManager->flush();
        }
    }
}