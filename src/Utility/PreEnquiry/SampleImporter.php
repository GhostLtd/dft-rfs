<?php


namespace App\Utility\PreEnquiry;


use App\Entity\LongAddress;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\SurveyInterface;
use App\Utility\AbstractBulkSurveyImporter;
use Symfony\Component\Form\FormInterface;

class SampleImporter extends AbstractBulkSurveyImporter
{
    public const COL_FIRM_REF = 0;
    public const COL_DISPATCH_DATE = 1;
    public const COL_FIRM_NAME = 2;
    public const COL_ADDRESS_1 = 3;
    public const COL_ADDRESS_2 = 4;
    public const COL_ADDRESS_3 = 5;
    public const COL_ADDRESS_4 = 6;
    public const COL_ADDRESS_5 = 7;
    public const COL_POSTCODE = 8;

    #[\Override]
    protected function getAggregateSurveyOptionsAndValidate(FormInterface $form): array
    {
        return [];
    }

    #[\Override]
    protected function parseLine($line): ?array
    {
        if ($fields = str_getcsv($line))
        {
            if (count($fields) !== 9) {
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
            return (new PreEnquiry())
                ->setCompanyName($surveyData[self::COL_FIRM_NAME])
                ->setReferenceNumber($surveyData[self::COL_FIRM_REF])
                ->setDispatchDate(new \DateTime($surveyData[self::COL_DISPATCH_DATE]))
                ->setInvitationAddress($this->createAddress($surveyData))
                ;
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
