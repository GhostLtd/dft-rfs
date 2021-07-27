<?php


namespace App\Utility\International;


use App\Entity\International\Company;
use App\Entity\International\Survey;
use App\Entity\LongAddress;
use App\Utility\AbstractBulkSurveyImporter;
use Symfony\Component\Form\FormInterface;

class SampleImporter extends AbstractBulkSurveyImporter
{
    const COL_FIRM_REF = 0;
    const COL_DISPATCH_WEEK = 1;
    const COL_START_DATE = 2;
    const COL_END_DATE = 3;
    const COL_FIRM_NAME = 4;
    const COL_ADDRESS_1 = 5;
    const COL_ADDRESS_2 = 6;
    const COL_ADDRESS_3 = 7;
    const COL_ADDRESS_4 = 8;
    const COL_ADDRESS_5 = 9;
    const COL_POSTCODE = 10;

    protected function getAggregateSurveyOptionsAndValidate(FormInterface $form)
    {
        return [];
    }

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

    public function createSurvey($surveyData, $surveyOptions = null)
    {
        try {
            return (new Survey())
                ->setSurveyPeriodStart(new \DateTime($surveyData[self::COL_START_DATE]))
                ->setSurveyPeriodEnd(new \DateTime($surveyData[self::COL_END_DATE]))
                ->setCompany((new Company())->setBusinessName($surveyData[self::COL_FIRM_NAME]))
                ->setReferenceNumber("{$surveyData[self::COL_FIRM_REF]}-{$surveyData[self::COL_DISPATCH_WEEK]}")
                ->setInvitationAddress($this->createAddress($surveyData))
                ;
        } catch (\Throwable $e) {
            return false;
        }
    }

    protected function createAddress($surveyData)
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