<?php

namespace App\Utility\International;

use App\Entity\International\Company;
use App\Entity\International\Survey;
use App\Entity\LongAddress;
use App\Entity\SurveyInterface;
use App\Utility\AbstractBulkSurveyImporter;
use Symfony\Component\Form\FormInterface;

class SampleImporter extends AbstractBulkSurveyImporter
{
    public const COL_FIRM_REF = 0;
    public const COL_DISPATCH_WEEK = 1;
    public const COL_START_DATE = 2;
    public const COL_END_DATE = 3;
    public const COL_FIRM_NAME = 4;
    public const COL_ADDRESS_1 = 5;
    public const COL_ADDRESS_2 = 6;
    public const COL_ADDRESS_3 = 7;
    public const COL_ADDRESS_4 = 8;
    public const COL_ADDRESS_5 = 9;
    public const COL_POSTCODE = 10;

    #[\Override]
    protected function getAggregateSurveyOptionsAndValidate(FormInterface $form)
    {
        return [];
    }

    #[\Override]
    protected function parseLine($line)
    {
        if ($fields = str_getcsv($line))
        {
            if (count($fields) !== 11) {
                return null;
            }
            return $fields;
        }
        return null;
    }

    #[\Override]
    public function createSurvey($surveyData, $surveyOptions = null): ?SurveyInterface
    {
        try {
            $survey = (new Survey())
                ->setSurveyPeriodStart(new \DateTime($surveyData[self::COL_START_DATE]))
                ->setSurveyPeriodEnd(new \DateTime($surveyData[self::COL_END_DATE]))
                ->setCompany((new Company())->setBusinessName($surveyData[self::COL_FIRM_NAME]))
                ->setReferenceNumber("{$surveyData[self::COL_FIRM_REF]}-{$surveyData[self::COL_DISPATCH_WEEK]}")
                ->setInvitationAddress($this->createAddress($surveyData))
                ;
            return $this->notificationInterception->checkAndInterceptSurvey($survey);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function createAddress($surveyData): LongAddress
    {
        return (new LongAddress())
            ->setLine1($surveyData[self::COL_FIRM_NAME])
            ->setLine2($surveyData[self::COL_ADDRESS_1])
            ->setLine3($surveyData[self::COL_ADDRESS_2])
            ->setLine4($surveyData[self::COL_ADDRESS_3])
            ->setLine5($surveyData[self::COL_ADDRESS_4])
            ->setLine6($surveyData[self::COL_ADDRESS_5])
            ->setPostcode($surveyData[self::COL_POSTCODE])
            ;
    }

}
