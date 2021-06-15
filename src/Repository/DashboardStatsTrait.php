<?php

namespace App\Repository;

trait DashboardStatsTrait
{
    private static array $statusColourMap = [
        "in-progress" => [
            "background" => "#ffdd00", // Yellow
            "colour" => "#000",
        ],
        "new" => [
            "background" => "#b1b4b6", // Mid-grey
            "colour" => "#000",
        ],
        "invitation pending" => [
            "background" => "#b1b4b6", // Mid-grey
            "colour" => "#000",
        ],
        "invitation failed" => [
            "background" => "#942514", // Red
            "colour" => "#fff",
        ],
        "invited" => [
            "background" => "#b1b4b6", // Mid-grey
            "colour" => "#000",
        ],
        "closed" => [
            "background" => "#1d70b8", // Blue
            "colour" => "#fff",
        ],
        "approved" => [
            "background" => "#005a30", // Green
            "colour" => "#fff",
        ],
        "rejected" => [
            "background" => "#942514", // Red
            "colour" => "#fff",
        ],
        "exporting" => [
            "background" => "#005a30", // Green
            "colour" => "#fff",
        ],
        "exported" => [
            "background" => "#005a30", // Green
            "colour" => "#fff",
        ],
    ];

    public function getCountsByStatus(): array
    {
        $wantedStates = ['in-progress', 'invited', 'approved'];
        $ignoredStates = ['exporting', 'exported', 'rejected'];
        $mergeStates = [
            'new' => 'invited',
            'invitation pending' => 'invited',
            'exporting' => 'exported',
        ];

        $counts = $this->createQueryBuilder('s')
            ->select('s.state, count(s) AS count')
            ->groupBy('s.state')
            ->getQuery()
            ->execute();

        // Add any missing but wanted states into the mix with a count of zero
        foreach($wantedStates as $state) {
            foreach($counts as $count) {
                if ($count['state'] === $state) {
                    continue 2;
                }
            }
            $counts[] = ['state' => $state, 'count' => 0];
        }

        $counts = $this->mergeCounts($counts, $mergeStates);

        $result = [];
        foreach($counts as $count) {
            $state = $count['state'];

            if (in_array($state, $ignoredStates)) {
                continue;
            }

            $colours = self::$statusColourMap[$state];
            $result[ucfirst($state)] = [
                'background' => $colours['background'],
                'colour' => $colours['colour'],
                'count' => $count['count'],
            ];
        }

        return $result;
    }

    private function mergeCounts($counts, array $mergeStates): array
    {
        $mergedCounts = [];

        foreach ($counts as $count) {
            $state = $count['state'];
            if (!isset($mergeStates[$state])) {
                $mergedCounts[$state] = $count;
            }
        }

        foreach ($counts as $count) {
            $state = $count['state'];
            if (isset($mergeStates[$state])) {
                $target = $mergeStates[$state];

                if (isset($mergedCounts[$target])) {
                    $mergedCounts[$target]['count'] += $count['count'];
                } else {
                    $mergedCounts[$target] = $count;
                    $mergedCounts[$target]['state'] = $target;
                }
            }
        }

        return array_values($mergedCounts);
    }
}