<?php

namespace App\Utility\Notification;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;

/**
 * Adds notification-history filters to a survey query.
 * Type-specific aliases and parameters allow several filters on the same QueryBuilder.
 */
final class NotificationQueryHelper
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * Excludes surveys with a notification of $type and returns the modified QueryBuilder.
     */
    public function withoutNotification(QueryBuilder $qb, string $alias, string $notificationClass, NotificationType $type): QueryBuilder
    {
        $t = $this->parameterSuffix($type);
        $sub = $this->buildSurveySubquery("none_{$t}.id", $notificationClass, "none_{$t}", $alias, "none_{$t}.type = :none_type_{$t}");

        return $qb->andWhere($qb->expr()->not($qb->expr()->exists($sub)))
            ->setParameter("none_type_{$t}", $type->value);
    }

    /**
     * Keeps surveys whose latest notification of $type was sent at or before $cutoff.
     * Excludes surveys with no such notification. Returns the modified QueryBuilder.
     */
    public function withLatestNotificationBy(QueryBuilder $qb, string $alias, string $notificationClass, NotificationType $type, \DateTimeInterface $cutoff): QueryBuilder
    {
        $t = $this->parameterSuffix($type);
        $latest = $this->buildSurveySubquery("MAX(latest_{$t}.sentAt)", $notificationClass, "latest_{$t}", $alias, "latest_{$t}.type = :latest_type_{$t}");

        return $qb->andWhere("({$latest}) <= :latest_cutoff_{$t}")
            ->setParameter("latest_type_{$t}", $type->value)
            ->setParameter("latest_cutoff_{$t}", \DateTime::createFromInterface($cutoff));
    }

    /**
     * Returns a DQL subquery selecting notification rows for the aliased survey.
     */
    private function buildSurveySubquery(string $select, string $notificationClass, string $subAlias, string $surveyAlias, string $condition): string
    {
        return $this->entityManager->createQueryBuilder()
            ->select($select)
            ->from($notificationClass, $subAlias)
            ->where("{$subAlias}.survey = {$surveyAlias}")
            ->andWhere($condition)
            ->getDQL();
    }

    /**
     * Returns the notification type as a valid suffix for DQL aliases and parameter names.
     */
    private function parameterSuffix(NotificationType $type): string
    {
        return str_replace('-', '_', $type->value);
    }
}
