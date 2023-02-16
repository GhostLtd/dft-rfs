<?php

namespace App\Utility\RoRo;

use App\Entity\RoRo\Operator;
use App\Repository\RoRo\OperatorRepository;
use App\Repository\RoRo\SurveyRepository;

class OperatorUsageHelper
{
    protected array $operatorSurveyCounts;

    protected OperatorRepository $operatorRepository;
    protected SurveyRepository $surveyRepository;

    public function __construct(OperatorRepository $operatorRepository, SurveyRepository $surveyRepository)
    {
        $this->operatorSurveyCounts = [];

        $this->operatorRepository = $operatorRepository;
        $this->surveyRepository = $surveyRepository;
    }

    public function hasOperatorSubmittedSurveys(Operator $operator): bool
    {
        $id = $operator->getId();
        $count = $this->operatorSurveyCounts[$id] ?? null;

        if ($count === null) {
            $count = $this->surveyRepository
                ->createQueryBuilder('s')
                ->select('COUNT(s)')
                ->join('s.operator', 'o')
                ->where('o.id = :operatorId')
                ->setParameter('operatorId', $id)
                ->getQuery()
                ->getSingleScalarResult();

            $this->operatorSurveyCounts[$id] = $count;
        }

        return $count > 0;
    }

    public function preFetchOperatorSurveyCounts(array $operators): self
    {
        $ids = array_map(fn(Operator $operator) => $operator->getId(), $operators);

        $operatorCounts = $this->operatorRepository
            ->createQueryBuilder('o')
            ->select('o.id, COUNT(s) AS survey_count')
            ->leftJoin('o.surveys', 's')
            ->where('o.id IN (:ids)')
            ->groupBy('o.id')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        foreach($operatorCounts as ['id' => $id, 'survey_count' => $surveyCount]) {
            $this->operatorSurveyCounts[$id] = $surveyCount;
        }

        return $this;
    }
}