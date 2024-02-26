<?php

namespace App\Utility\AuditEntityLogger;

use App\Entity\SurveyStateInterface;

class SurveyStateLogger extends AbstractAuditEntityLogger
{
    public const CATEGORY = 'survey-state';

    #[\Override]
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

    #[\Override]
    public function supports(string $className): bool
    {
        return $this->implementsInterface($className, SurveyStateInterface::class);
    }

    #[\Override]
    function getCategory(): string
    {
        return self::CATEGORY;
    }
}