<?php

namespace App\Repository;

use App\Entity\SurveyInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

abstract class AbstractSurveyRepository extends ServiceEntityRepository
{
    protected function doGetRejectedSurveysThatHaveUsersBefore(\DateTime $rejectedBefore, string $tableName): array {
        $statement = $this->getEntityManager()
            ->getConnection()
            ->getWrappedConnection()
            ->prepare(<<<EOQ
SELECT s.id, s.state, a.timestamp, a.data FROM audit_log a
INNER JOIN {$tableName} s ON a.entity_id = s.id
INNER JOIN passcode_user pu on s.id = pu.{$tableName}_id
WHERE a.category = "survey-state"
GROUP BY a.entity_id
HAVING a.timestamp = MAX(a.timestamp)
AND a.data LIKE "%\"to\":\"rejected\"%"
AND s.state = "rejected"
AND a.timestamp < :timestamp
EOQ
            );

        $statement->bindValue('timestamp', $rejectedBefore->format('Y-m-d H:i:s'));
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
}