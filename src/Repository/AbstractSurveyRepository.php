<?php

namespace App\Repository;

use App\Entity\SurveyInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AbstractSurveyRepository extends ServiceEntityRepository
{
    protected function doGetSurveysRequiringPasscodeUserCleanup(\DateTime $before, string $tableName): array {
        $statement = $this->getEntityManager()
            ->getConnection()
            ->getWrappedConnection()
            ->prepare(<<<EOQ
SELECT s.id FROM audit_log a
INNER JOIN (
    SELECT entity_id, MAX(timestamp) AS timestamp
    FROM audit_log
    WHERE category = "survey-state"
    GROUP BY entity_id
    ) m ON m.entity_id = a.entity_id AND m.timestamp = a.timestamp
INNER JOIN {$tableName} s ON a.entity_id = s.id
INNER JOIN passcode_user pu on s.id = pu.{$tableName}_id
WHERE NOT EXISTS (
    SELECT a2.id FROM audit_log a2 
    WHERE a2.category = "cleanup-passcode"
    AND a2.entity_id = s.id
)
AND a.data LIKE "%""to"":""rejected""%" 
AND s.state = "rejected"
AND a.timestamp < :rejected_before
EOQ
            );

        $statement->bindValue('rejected_before', $before->format('Y-m-d H:i:s'));
        $statement->execute();

        $surveyIds = [];
        while(($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $surveyIds[] = $row['id'];
        }

        return $this->createQueryBuilder('s')
            ->select('s,p')
            ->innerJoin('s.passcodeUser', 'p') // i.e. passcodeUser must not be NULL
            ->where('s.state IN (:states)')
            ->andWhere('s.id IN (:ids)')
            ->setParameter('states', [SurveyInterface::STATE_REJECTED])
            ->setParameter('ids', $surveyIds)
            ->getQuery()
            ->execute();
    }

    public function doGetSurveysRequiringPersonalDataCleanup(\DateTime $before, array $states, string $tableName): array {
        $states = array_values($states); // Re-index

        $stateParts = [];
        foreach($states as $i => $state) {
            $stateParts[] = '(a.data LIKE :stateLike'.$i.' AND s.state=:state'.$i.')';
        }

        $statesPart = 'AND ('.join(' OR ', $stateParts).')';

        $sql = <<<EOQ
SELECT s.id FROM audit_log a
INNER JOIN (
        SELECT entity_id, MAX(timestamp) AS timestamp
        FROM audit_log
        WHERE category = "survey-state"
        GROUP BY entity_id
    ) m ON m.entity_id = a.entity_id AND m.timestamp = a.timestamp
INNER JOIN {$tableName} s ON a.entity_id = s.id
WHERE NOT EXISTS (
    SELECT a2.id FROM audit_log a2 
    WHERE a2.category = "cleanup-personal"
    AND a2.entity_id = s.id
)
{$statesPart}
AND a.timestamp < :timestamp
EOQ;

        $statement = $this->getEntityManager()
            ->getConnection()
            ->getWrappedConnection()
            ->prepare($sql);

        $statement->bindValue('timestamp', $before->format('Y-m-d H:i:s'));

        foreach($states as $i => $state) {
            $statement->bindValue("stateLike{$i}", '%"to":"'.$state.'"%');
            $statement->bindValue("state{$i}", $state);
        }

        $statement->execute();

        $surveyIds = [];
        while(($row = $statement->fetch(\PDO::FETCH_ASSOC)) !== false) {
            $surveyIds[] = $row['id'];
        }

        return $this->createQueryBuilder('s')
            ->select('s')
            ->where('s.state IN (:states)')
            ->andWhere('s.id IN (:ids)')
            ->setParameter('states', $states)
            ->setParameter('ids', $surveyIds)
            ->getQuery()
            ->execute();
    }
}