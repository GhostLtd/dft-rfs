<?php

namespace App\Utility\Domestic;

use App\Entity\Domestic\Survey;
use Doctrine\ORM\EntityManagerInterface;

class OnHireStatsProvider
{
    protected array $cachedResults;

    public function __construct(protected EntityManagerInterface $entityManager)
    {
        $this->cachedResults = [];
    }

    public function preloadStatsForSurveys(iterable $surveys): void
    {
        $companyNames = array_map(
            fn(Survey $survey) => $survey->getCompanyNameFromInvitationAddress(),
            iterator_to_array($surveys)
        );

        $companyNames = array_filter(
            $companyNames,
            fn(?string $companyName) => $companyName !== null
        );

        $this->preloadStatsForCompanyNames($companyNames);
    }

    /**
     * @param array<string> $companyNames
     */
    public function preloadStatsForCompanyNames(array $companyNames): void
    {
        $results = $this->entityManager->createQueryBuilder()
            ->select([
                's.invitationAddress.line1 AS companyName',
                "CAST(SUM(IF(r.isInPossessionOfVehicle = 'on-hire', 1, 0)) AS SIGNED) AS onHireCount",
                "COUNT(r.id) AS total",
            ])
            ->from(Survey::class, 's', 's.invitationAddress.line1')
            ->leftJoin('s.response', 'r')
            ->where('s.invitationAddress.line1 IN (:companyNames)')
            ->groupBy('s.invitationAddress.line1')
            ->orderBy('s.invitationAddress.line1')
            ->setParameter('companyNames', $companyNames)
            ->getQuery()
            ->getArrayResult();

        $this->cachedResults = array_merge($this->cachedResults, $results);
    }

    public function isLikelyHireCompany(?string $companyName, float $threshold = 50.0): bool
    {
        $stats = $this->getOnHireStats($companyName);

        if (!$stats) {
            return false;
        }

        return $stats['percentage'] >= $threshold;
    }

    public function getOnHireStats(?string $companyName): ?array
    {
        if (!$companyName) {
            return null;
        }

        if (!in_array($companyName, $this->cachedResults)) {
            $this->preloadStatsForCompanyNames([$companyName]);
        }

        $stats = $this->cachedResults[$companyName] ?? null;

        if (!$stats) {
            return null;
        }

        ['total' => $total, 'onHireCount' => $onHireCount] = $stats;

        $stats['percentage'] = $onHireCount === 0 ?
            0 :
            ($onHireCount / $total) * 100;

        return $stats;
    }
}
