<?php

namespace App\Utility\Notification;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * The two notification-row predicates every family's eligibility query needs, added to the caller's
 * QueryBuilder. Sub-query aliases and parameter names include the type so several calls compose on one
 * builder.
 */
final class NotificationQueryHelper
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /** AND NOT EXISTS (a row of $type for the survey aliased $alias). */
    public function none(QueryBuilder $qb, string $alias, string $notificationClass, NotificationType $type): QueryBuilder
    {
        $t = $this->tag($type);
        $sub = $this->subquery("none_{$t}.id", $notificationClass, "none_{$t}", $alias, "none_{$t}.type = :none_type_{$t}");

        return $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub)))
            ->setParameter("none_type_{$t}", $type->value);
    }

    /**
     * AND the LATEST row of $type for the survey is at or before $cutoff. A survey with no such row has a NULL
     * latest, so fails the comparison; a survey with an old row and a recent one is NOT matched.
     */
    public function latestAtOrBefore(QueryBuilder $qb, string $alias, string $notificationClass, NotificationType $type, \DateTimeInterface $cutoff): QueryBuilder
    {
        $t = $this->tag($type);
        $latest = $this->subquery("MAX(latest_{$t}.sentAt)", $notificationClass, "latest_{$t}", $alias, "latest_{$t}.type = :latest_type_{$t}");

        return $qb->andWhere("({$latest}) <= :latest_cutoff_{$t}")
            ->setParameter("latest_type_{$t}", $type->value)
            ->setParameter("latest_cutoff_{$t}", \DateTime::createFromInterface($cutoff));
    }

    /** SELECT $select FROM $notificationClass rows of the survey aliased $surveyAlias that match $condition */
    private function subquery(string $select, string $notificationClass, string $subAlias, string $surveyAlias, string $condition): string
    {
        return $this->entityManager->createQueryBuilder()
            ->select($select)
            ->from($notificationClass, $subAlias)
            ->where("{$subAlias}.survey = {$surveyAlias}")
            ->andWhere($condition)
            ->getDQL();
    }

    private function tag(NotificationType $type): string
    {
        return str_replace('-', '_', $type->value);
    }
}
