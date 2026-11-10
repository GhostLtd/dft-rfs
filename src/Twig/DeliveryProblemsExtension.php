<?php

namespace App\Twig;

use App\Entity\SurveyNotificationsInterface;
use App\Utility\Notification\DeliveryProblems;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class DeliveryProblemsExtension extends AbstractExtension
{
    public function __construct(private readonly DeliveryProblems $deliveryProblems) {}

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('delivery_problems', $this->summary(...)),
            new TwigFunction('delivery_problem_counts', $this->counts(...)),
        ];
    }

    /**
     * Delivery problem counts for a list page's surveys, in one query; surveys without problems are left out.
     *
     * @param iterable<SurveyNotificationsInterface|array{0: SurveyNotificationsInterface}> $entities a list page's
     *        entities (a row may be an array, with the survey first, when the list selects extra columns)
     * @return array<string, array{count: int, unseen: int}> keyed by survey id
     */
    public function counts(iterable $entities): array
    {
        $surveys = [];
        foreach ($entities as $entity) {
            $surveys[] = is_array($entity) ? $entity[0] : $entity;
        }

        if ($surveys === []) {
            return [];
        }

        return $this->deliveryProblems->countsForSurveys(
            $surveys[0]::getNotificationClass(),
            array_map(fn(SurveyNotificationsInterface $survey) => $survey->getId(), $surveys),
        );
    }

    /**
     * @return array{count: int, unseen: int, type: ?string}
     */
    public function summary(SurveyNotificationsInterface $survey): array
    {
        return $this->deliveryProblems->summary($survey) + ['type' => DeliveryProblems::surveyType($survey)];
    }
}
