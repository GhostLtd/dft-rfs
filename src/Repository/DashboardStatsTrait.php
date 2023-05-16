<?php

namespace App\Repository;

use App\Entity\SurveyStateInterface;

trait DashboardStatsTrait
{
    private static array $statusColourMap = [
        SurveyStateInterface::STATE_IN_PROGRESS => [
            "background" => "#ffdd00", // Yellow
            "colour" => "#000",
        ],
        SurveyStateInterface::STATE_NEW => [
            "background" => "#b1b4b6", // Mid-grey
            "colour" => "#000",
        ],
        SurveyStateInterface::STATE_INVITATION_PENDING => [
            "background" => "#b1b4b6", // Mid-grey
            "colour" => "#000",
        ],
        SurveyStateInterface::STATE_INVITATION_FAILED => [
            "background" => "#942514", // Red
            "colour" => "#fff",
        ],
        SurveyStateInterface::STATE_INVITATION_SENT => [
            "background" => "#b1b4b6", // Mid-grey
            "colour" => "#000",
        ],
        SurveyStateInterface::STATE_CLOSED => [
            "background" => "#1d70b8", // Blue
            "colour" => "#fff",
        ],
        SurveyStateInterface::STATE_APPROVED => [
            "background" => "#005a30", // Green
            "colour" => "#fff",
        ],
        SurveyStateInterface::STATE_REJECTED => [
            "background" => "#942514", // Red
            "colour" => "#fff",
        ],
        SurveyStateInterface::STATE_EXPORTING => [
            "background" => "#005a30", // Green
            "colour" => "#fff",
        ],
        SurveyStateInterface::STATE_EXPORTED => [
            "background" => "#005a30", // Green
            "colour" => "#fff",
        ],
    ];

    public function getCountsByStatus(): array
    {
        $wantedStates = [
            SurveyStateInterface::STATE_INVITATION_SENT,
            SurveyStateInterface::STATE_IN_PROGRESS,
            SurveyStateInterface::STATE_APPROVED,
            SurveyStateInterface::STATE_CLOSED,
        ];

        $ignoredStates = [
            SurveyStateInterface::STATE_EXPORTING,
            SurveyStateInterface::STATE_EXPORTED,
            SurveyStateInterface::STATE_REJECTED,
        ];

        $mergeStates = [
            SurveyStateInterface::STATE_NEW => SurveyStateInterface::STATE_INVITATION_SENT,
            SurveyStateInterface::STATE_INVITATION_PENDING => SurveyStateInterface::STATE_INVITATION_SENT,
            SurveyStateInterface::STATE_EXPORTING => SurveyStateInterface::STATE_EXPORTED,
        ];

        $counts = $this->createQueryBuilder('s')
            ->select('s.state, count(s) AS count')
            ->groupBy('s.state')
            ->getQuery()
            ->execute();

        // Add any missing but wanted states into the mix with a count of zero

        $restructuredCounts = [];
        foreach($counts as $count) {
            $restructuredCounts[$count['state']] = $count['count'];
        }

        $counts = $this->addMissingCounts($restructuredCounts, $wantedStates);
        $counts = $this->mergeCounts($counts, $mergeStates);
        $counts = $this->orderCounts($counts, $wantedStates);

        $result = [];
        foreach($counts as $state => $count) {
            if (in_array($state, $ignoredStates)) {
                continue;
            }

            $colours = self::$statusColourMap[$state];
            $result[ucfirst($state)] = [
                'background' => $colours['background'],
                'colour' => $colours['colour'],
                'count' => $count,
            ];
        }

        return $result;
    }

    private function mergeCounts($counts, array $mergeStates): array
    {
        $mergedCounts = [];

        foreach ($counts as $state => $count) {
            if (!isset($mergeStates[$state])) {
                $mergedCounts[$state] = $count;
            }
        }

        foreach ($counts as $state => $count) {
            if (isset($mergeStates[$state])) {
                $target = $mergeStates[$state];

                if (isset($mergedCounts[$target])) {
                    $mergedCounts[$target] += $count;
                } else {
                    $mergedCounts[$target] = $count;
                }
            }
        }

        return $mergedCounts;
    }

    private function addMissingCounts(array $counts, array $wantedStates): array
    {
        foreach ($wantedStates as $state) {
            if (!isset($counts[$state])) {
                $counts[$state] = 0;
            }
        }
        return $counts;
    }

    private function orderCounts(array $counts, array $wantedStates): array
    {
        $orderedCounts = [];
        foreach($wantedStates as $state) {
            $orderedCounts[$state] = $counts[$state];
        }
        return $orderedCounts;
    }
}