<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\NotificationInterceptionAdvancedInterface;
use App\Entity\NotificationInterceptionInterface;
use App\Entity\SurveyInterface;
use App\Repository\Domestic\NotificationInterceptionRepository as DomesticNotificationInterceptionRepository;
use App\Repository\International\NotificationInterceptionRepository as InternationalNotificationInterceptionRepository;
use App\Repository\NotificationInterceptionRepositoryInterface;
use Doctrine\ORM\PersistentCollection;

class NotificationInterceptionService
{
    /** @var array|NotificationInterceptionRepositoryInterface[]  */
    private array $repositories;

    /** @var array | PersistentCollection */
    private $interceptions;

    public function __construct(DomesticNotificationInterceptionRepository $domesticRepository, InternationalNotificationInterceptionRepository $internationalRepository)
    {
        $this->repositories = [
            DomesticSurvey::class => $domesticRepository,
            InternationalSurvey::class => $internationalRepository,
        ];
    }

    protected function getRepositoryByNotificationInterception(NotificationInterceptionInterface $notificationInterception): NotificationInterceptionRepositoryInterface
    {
        foreach ($this->repositories as $repository) {
            if ($repository->getClassName() === $notificationInterception::class) {
                return $repository;
            }
        }
        throw new \RuntimeException("unhandled notification interception type");
    }

    public function checkAndInterceptSurvey(SurveyInterface $survey): SurveyInterface
    {
        if ($survey->hasValidInvitationEmails()) {
            return $survey;
        }
        if (!$survey->getInvitationAddress()) {
            return $survey;
        }

        if ($interception = $this->findInterception($survey)) {
            $survey->setInvitationEmails($interception->getEmails());
        }

        return $survey;
    }

    public function getNonUniqueCompanyNames(NotificationInterceptionAdvancedInterface $notificationInterception): array
    {
        return $this->getRepositoryByNotificationInterception($notificationInterception)->findByAllNames(
            $notificationInterception->getId(),
            [$notificationInterception->getPrimaryName(), ...array_map(fn($n) => $n->getName(), $notificationInterception->getAdditionalNames()->toArray())]
        );
    }

    protected function findInterception(SurveyInterface $survey): ?NotificationInterceptionInterface
    {
        return $this->repositories[$survey::class]->findOneBySurvey($survey);
    }
}