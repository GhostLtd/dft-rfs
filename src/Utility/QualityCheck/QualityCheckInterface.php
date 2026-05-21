<?php

namespace App\Utility\QualityCheck;

use App\Entity\Domestic\SurveyResponse;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('csrgt.quality_check')]
interface QualityCheckInterface
{
    /** @return QualityFlag[] */
    public function run(SurveyResponse $response): array;
}
