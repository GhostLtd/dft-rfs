<?php

namespace App\Utility\Cleanup;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use Doctrine\ORM\EntityManagerInterface;

class PasscodeUserCleanupUtility
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @throws \Exception
     */
    public function cleanupPasscodeUsersBefore(\DateTime $before): int
    {
        $count = 0;

        $matchingDomesticSurveys = $this->entityManager->getRepository(DomesticSurvey::class)
            ->getSurveysRequiringPasscodeUserCleanup($before);

        $matchingInternationalSurveys = $this->entityManager->getRepository(InternationalSurvey::class)
            ->getSurveysRequiringPasscodeUserCleanup($before);

        $this->entityManager->beginTransaction();
        try {
            foreach ($matchingDomesticSurveys as $survey) {
                $this->entityManager->remove($survey->getPasscodeUser());
                $survey->setPasscodeUser(null);
                $this->entityManager->persist($this->getAuditLogEntry($survey));
                $count++;
            }

            foreach ($matchingInternationalSurveys as $survey) {
                $this->entityManager->remove($survey->getPasscodeUser());
                $survey->setPasscodeUser(null);
                $this->entityManager->persist($this->getAuditLogEntry($survey));
                $count++;
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        }
        catch(\Exception $e) {
            $this->entityManager->rollback();
            throw $e;
        }

        return $count;
    }

    protected function getAuditLogEntry(SurveyInterface $survey): AuditLog {
        return (new AuditLog())
            ->setCategory('cleanup-passcode')
            ->setUsername('-')
            ->setEntityId($survey->getId())
            ->setEntityClass(get_class($survey))
            ->setTimestamp(new \DateTime())
            ->setData([]);
    }
}