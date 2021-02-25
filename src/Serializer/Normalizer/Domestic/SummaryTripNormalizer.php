<?php


namespace App\Serializer\Normalizer\Domestic;


use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Vehicle as BaseVehicle;
use App\Entity\Volume;
use App\Serializer\Normalizer\AbstractExportNormalizer;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanEquivalencyProperty;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanLiteral;
use App\Serializer\Normalizer\Domestic\Mapper\EpochWeekNumberProperty;
use App\Serializer\Normalizer\Domestic\Mapper\SurveyTypeProperty;
use App\Serializer\Normalizer\Mapper\Property;
use App\Serializer\Normalizer\Domestic\Mapper\BooleanProperty;

class SummaryTripNormalizer extends AbstractExportNormalizer
{
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof DaySummary;
    }

    protected function getMapping(): array
    {
        $true = new BooleanLiteral(true);
        $false = new BooleanLiteral(false);

        return [
            'QDJournerysFiveorMoreID' => '',
            'QDSurveyDetailsID' => '',
            'RegMark' => '',
            'Origin' => '',
            'Destination' => '',
            'TypeOfGoods' => '',
            'Loaded' => '',
            'Empty' => '',
            'WeightOfGoodsDelivered' => '',
            'WeightOfGoodsCollected' => '',
            'NumberOfStopsForDelivery' => '',
            'NumberOfStopsForCollection' => '',
            'NumberOfStopsForBoth' => '',
            'DateInput' => '',
            'RowNumber' => '',
            'FurthestStop' => '',
            'MOA' => '',
            'WhereBorderCrossed' => '',
            'DayOfWeek' => '',
            'MOACode' => '',
            'FromPort' => '',
            'FromAir' => '',
            'FromRail' => '',
            'ToPort' => '',
            'ToAir' => '',
            'ToRail' => '',
            'DangerousHazardousGoodsID' => '',

        ];
    }
}