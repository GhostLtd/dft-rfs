<?php

namespace App\Utility\Notification;

use App\Controller\Admin\Domestic\SurveyController as DomesticSurveyController;
use App\Controller\Admin\International\SurveyController as InternationalSurveyController;
use App\Controller\Admin\PreEnquiry\EditController as PreEnquiryController;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\NotifyApiResponse;
use App\Entity\NotifyCallback;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyNotificationsInterface;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Delivery problems for a survey: responses that failed at send time, or that Notify later reported as a fatal
 * outcome (see NotifyApiResponse::hasDeliveryProblem()), and whether an admin has seen them.
 */
class DeliveryProblems
{
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /** Admin route segment => [survey class, admin view route, view route's id parameter] */
    public const array SURVEY_TYPES = [
        'csrgt' => [DomesticSurvey::class, DomesticSurveyController::VIEW_ROUTE, 'surveyId'],
        'irhs' => [InternationalSurvey::class, InternationalSurveyController::VIEW_ROUTE, 'surveyId'],
        'pre-enquiry' => [PreEnquiry::class, PreEnquiryController::VIEW_ROUTE, 'preEnquiryId'],
    ];

    /**
     * @return list<NotifyApiResponse>
     */
    public function responsesWithProblems(SurveyNotificationsInterface $survey): array
    {
        $responses = [];
        foreach ($survey->getNotifications() as $notification) {
            if (!$notification instanceof ApiResponseInterface) {
                continue;
            }
            foreach ($notification->getNotifyApiResponses() as $response) {
                if ($response->hasDeliveryProblem()) {
                    $responses[] = $response;
                }
            }
        }

        return $responses;
    }

    /**
     * Delivery problem counts for a page of surveys, in one query (for list pages). Surveys without problems are
     * left out.
     *
     * @param class-string $notificationClass the survey type's notification entity
     * @param list<string> $surveyIds
     * @return array<string, array{count: int, unseen: int}> keyed by survey id
     */
    public function countsForSurveys(string $notificationClass, array $surveyIds): array
    {
        if ($surveyIds === []) {
            return [];
        }

        $callbackClass = NotifyCallback::class;
        $rows = $this->entityManager->createQuery(<<<DQL
            SELECT IDENTITY(n.survey) AS surveyId, r.problemSeenAt AS seenAt,
                   GHOST_JSON_GET(r.data, '\$."symfony_messenger_bus.govuk_notify_status"') AS status,
                   (SELECT COUNT(c.id) FROM {$callbackClass} c WHERE c.response = r AND c.status IN (:fatal)) AS fatalCallbacks
              FROM {$notificationClass} n
              JOIN n.apiResponses r
             WHERE n.survey IN (:surveyIds)
        DQL)
            ->setParameter('fatal', NotifyCallback::FATAL_STATUSES)
            ->setParameter('surveyIds', $surveyIds)
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            if ($row['status'] !== Reference::STATUS_FAILED && (int) $row['fatalCallbacks'] === 0) {
                continue;
            }

            $counts[$row['surveyId']] ??= ['count' => 0, 'unseen' => 0];
            $counts[$row['surveyId']]['count']++;
            if ($row['seenAt'] === null) {
                $counts[$row['surveyId']]['unseen']++;
            }
        }

        return $counts;
    }

    /**
     * @return array{count: int, unseen: int}
     */
    public function summary(SurveyNotificationsInterface $survey): array
    {
        $responses = $this->responsesWithProblems($survey);

        return [
            'count' => count($responses),
            'unseen' => count(array_filter($responses, fn(NotifyApiResponse $r) => $r->isDeliveryProblemUnseen())),
        ];
    }

    /**
     * Marks all the survey's delivery problems as seen (or unseen). The caller flushes.
     */
    public function markSeen(SurveyNotificationsInterface $survey, bool $seen, \DateTime $now): void
    {
        foreach ($this->responsesWithProblems($survey) as $response) {
            $response->setProblemSeenAt($seen ? clone $now : null);
        }
    }

    /** The admin route segment for this survey's type, or null if delivery problems aren't shown for it */
    public static function surveyType(SurveyNotificationsInterface $survey): ?string
    {
        $class = ClassUtils::getRealClass($survey::class);
        foreach (self::SURVEY_TYPES as $type => [$surveyClass]) {
            if ($class === $surveyClass) {
                return $type;
            }
        }

        return null;
    }
}
