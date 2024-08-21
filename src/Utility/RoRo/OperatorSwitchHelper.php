<?php

namespace App\Utility\RoRo;

use App\Entity\RoRo\Operator;
use App\Entity\RoRo\OperatorGroup;
use App\Repository\RoRo\OperatorRepository;

class OperatorSwitchHelper
{
    /** @var array<string, OperatorGroup> */
    protected array $operatorGroupMap = [];


    /** @var array<string, array<Operator>> */
    protected array $operatorSwitchTargetMap = [];

    public function __construct(
        protected OperatorRepository $operatorRepository,
    ) {}

    /**
     * Used to load the data we want, only when requested (i.e. lazy-load)
     */
    protected function lazyLoadDataForOperator(Operator $operator): void
    {
        $operatorId = $operator->getId();

        if (isset($this->operatorGroupMap[$operatorId])) {
            return;
        }

        $operatorGroup = $this->operatorRepository->findOperatorGroupForOperator($operator);
        $this->operatorGroupMap[$operatorId] = $operatorGroup;

        $this->operatorSwitchTargetMap[$operatorId] = $operatorGroup ?
            $this->operatorRepository->findOperatorsWithNamePrefix($operatorGroup->getName()) :
            [];
    }

    public function getOperatorGroup(Operator $operator): ?OperatorGroup
    {
        $this->lazyLoadDataForOperator($operator);
        return $this->operatorGroupMap[$operator->getId()];
    }

    public function getOperatorSwitchTargets(Operator $operator): array
    {
        $this->lazyLoadDataForOperator($operator);
        return $this->operatorSwitchTargetMap[$operator->getId()];
    }

    public function canSwitchBetweenOperators(Operator $from, Operator $to): bool
    {
        if ($from === $to) {
            return true;
        }

        $targets = $this->getOperatorSwitchTargets($from);

        return isset($targets[$to->getId()]);
    }

    public function canSwitchOperator(Operator $operator): bool
    {
        return count($this->getOperatorSwitchTargets($operator)) > 0;
    }
}
