<?php

namespace App\Utility\RoRo;

use App\Entity\RoRo\Operator;
use App\Repository\RoRo\OperatorRepository;
use App\Repository\RoRo\SurveyRepository;

class OperatorUsageHelper
{
    protected array $operatorSurveyCounts;

    public function __construct(protected OperatorRepository $operatorRepository, protected SurveyRepository $surveyRepository)
    {
        $this->operatorSurveyCounts = [];
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
}
