<?php

namespace App\Utility\AuditEntityLogger;

use App\Entity\HaulageSurveyInterface;
use App\Entity\QualityAssuranceInterface;

class SurveyQaLogger extends AbstractAuditEntityLogger
{
    public const CATEGORY = 'survey-qa';

    #[\Override]
    public function getAuditLogEntries(array $changeSets, string $username): array
    {
        $logs = [];

        /** @var ChangeSet $changeSet */
        foreach($changeSets as $changeSet) {
            if ($changeSet->getType() === ChangeSet::TYPE_UPDATE && $changeSet->has('qualityAssured')) {
                $logs[] = $this->createLog($username, $changeSet->getEntity(), []);
            }
        }

        return $logs;
    }

    #[\Override]
    public function supports(string $className): bool
    {
        return $this->implementsInterface($className, QualityAssuranceInterface::class);
    }

    #[\Override]
    function getCategory(): string
    {
        return self::CATEGORY;
    }
}