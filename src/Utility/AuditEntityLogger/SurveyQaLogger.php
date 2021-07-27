<?php

namespace App\Utility\AuditEntityLogger;

use App\Entity\HaulageSurveyInterface;

class SurveyQaLogger extends AbstractAuditEntityLogger
{
    const CATEGORY = 'survey-qa';

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

    public function supports(string $className): bool
    {
        return $this->implementsInterface($className, HaulageSurveyInterface::class);
    }

    function getCategory(): string
    {
        return self::CATEGORY;
    }
}