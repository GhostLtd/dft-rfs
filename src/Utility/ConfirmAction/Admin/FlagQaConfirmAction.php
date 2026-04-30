<?php

namespace App\Utility\ConfirmAction\Admin;

use App\Entity\International\Survey;
use App\Entity\QualityAssuranceInterface;
use Ghost\GovUkCoreBundle\Utility\ConfirmAction\AbstractConfirmAction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class FlagQaConfirmAction extends AbstractConfirmAction
{
    protected string $transition;

    /** @var Survey */
    protected mixed $subject;

    public function __construct(FormFactoryInterface $formFactory, RequestStack $requestStack, private EntityManagerInterface $entityManager)
    {
        parent::__construct($formFactory, $requestStack);
    }

    #[\Override]
    public function getTranslationDomain(): ?string
    {
        return 'admin';
    }

    #[\Override]
    function getTranslationParameters(): array
    {
        return [];
    }

    #[\Override]
    public function getTranslationKeyPrefix(): string
    {
        return "common.survey.flag-qa";
    }

    #[\Override]
    public function doConfirmedAction(mixed $formData): void
    {
        $survey = $this->getSubject();

        if ($survey instanceof QualityAssuranceInterface) {
            $survey->setQualityAssured(true);
            $this->entityManager->flush();
        }
    }
}