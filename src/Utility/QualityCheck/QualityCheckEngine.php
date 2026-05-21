<?php

namespace App\Utility\QualityCheck;

use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

class QualityCheckEngine
{
    /**
     * @param iterable<QualityCheckInterface> $checks
     */
    public function __construct(
        #[TaggedIterator('csrgt.quality_check')]
        protected iterable $checks,
    ) {}

    public function run(?SurveyResponse $response): FlagSet
    {
        $set = new FlagSet();
        if ($response === null) {
            return $set;
        }
        foreach ($this->checks as $check) {
            foreach ($check->run($response) as $flag) {
                $set->add($flag);
            }
        }
        return $set;
    }

    public function computeAndPersist(SurveyResponse $response): FlagSet
    {
        $set = $this->run($response);
        $response->setQualityFlagCount($set->count());
        $response->setQualityFlagSignature($set->signature());
        return $set;
    }
}
