<?php

namespace App\Utility\AuditEntityLogger;

use App\Entity\SurveyInterface;

class SurveyStateLogger extends AbstractAuditEntityLogger
{
    const CATEGORY = 'survey-state';

    public function getAuditLogEntries(array $changeSets, string $username): array
    {
        $logs = [];

        /** @var ChangeSet $changeSet */
        foreach($changeSets as $changeSet) {
            if ($changeSet->getType() === ChangeSet::TYPE_UPDATE && $changeSet->has('state')) {
                $stateChange = $changeSet->get('state');

                $logs[] = $this->createLog($username, $changeSet->getEntity(), [
                    'from' => $stateChange[0],
                    'to' => $stateChange[1]
                ]);
            }
        }

        return $logs;
    }

    public function supports(string $className): bool
    {
        return $this->implementsInterface($className, SurveyInterface::class);
    }

    function getCategory(): string
    {
        return self::CATEGORY;
    }
}