<?php

namespace App\EventSubscriber;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events as OrmEvents;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Mapping\MappingException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Security;

abstract class AbstractLcniChangeSubscriber implements EventSubscriber
{
    protected ClassMetadata $auditLogClassMetadata;
    protected ClassMetadata $surveyClassMetadata;
    protected EntityManagerInterface $entityManager;
    protected UnitOfWork $uow;

    public function __construct(
        protected LoggerInterface $logger,
        protected Security        $security,
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            OrmEvents::onFlush => 'onFlush',
        ];
    }

    public function initialise(string $surveyClass, EntityManager $entityManager): bool
    {
        try {
            $this->surveyClassMetadata = $entityManager->getClassMetadata($surveyClass);
            $this->auditLogClassMetadata = $entityManager->getClassMetadata(AuditLog::class);
        }
        catch(MappingException) {
            // We have two configured entityManagers (one normal, one for audit log)

            // The AuditLog entity manager is not relevant for this, and won't be able to resolve Survey metadata,
            // so we'll end up here and can exit/signal
            return false;
        }

        $this->entityManager = $entityManager;
        $this->uow = $this->entityManager->getUnitOfWork();

        return true;
    }

    /**
     * @param array<DomesticSurvey|InternationalSurvey> $surveys
     */
    protected function updateSurveysEmails(
        array   $surveys,
        ?string $emails,
        string  $auditLogCategory,
        array   $auditLogData,
        \Closure  $logTextCallback,
    ): void
    {
        foreach ($surveys as $survey) {
            $currentEmails = $survey->getInvitationEmails();

            if ($emails === $currentEmails) {
                continue;
            }

            $survey->setInvitationEmails($emails);
            $this->uow->computeChangeSet($this->surveyClassMetadata, $survey);

            $auditLog = $this->createAuditLog($survey, $auditLogCategory, $auditLogData);

            $this->uow->computeChangeSet($this->auditLogClassMetadata, $auditLog);

            $logText = $logTextCallback($survey);
            $this->logger->info($logText);
        }
    }

    protected function createAuditLog(DomesticSurvey|InternationalSurvey $survey, string $category, array $data): AuditLog
    {
        $username = $this->security->getUser()->getUserIdentifier();
        $auditLog = (new AuditLog())
            ->setCategory($category)
            ->setUsername($username)
            ->setEntityId($survey->getId())
            ->setEntityClass(get_class($survey))
            ->setTimestamp(new \DateTime())
            ->setData($data);

        $this->entityManager->persist($auditLog);
        return $auditLog;
    }
}