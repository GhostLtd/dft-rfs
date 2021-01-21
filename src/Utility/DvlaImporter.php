<?php


namespace App\Utility;


use App\Entity\Domestic\Survey;
use App\Entity\LongAddress;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class DvlaImporter
{
    const COL_REG_MARK = 'reg_mark';
    const COL_ADDRESS_1 = 'address_1';
    const COL_ADDRESS_2 = 'address_2';
    const COL_ADDRESS_3 = 'address_3';
    const COL_ADDRESS_4 = 'address_4';
    const COL_ADDRESS_5 = 'address_5';
    const COL_ADDRESS_6 = 'address_6';
    const COL_POSTCODE = 'postcode';
    const COL_UNKNOWN_1 = 'unknown_1';
    const COL_YEAR_MFR = 'year_of_mfr';
    const COL_UNKNOWN_2 = 'unknown_2';
    const COL_GROSS_WEIGHT = 'gross_weight';

    const COLUMN_WIDTHS = [
        self::COL_REG_MARK => 7,
        self::COL_ADDRESS_1 => 50,
        self::COL_ADDRESS_2 => 30,
        self::COL_ADDRESS_3 => 30,
        self::COL_ADDRESS_4 => 30,
        self::COL_ADDRESS_5 => 30,
        self::COL_ADDRESS_6 => 30,
        self::COL_POSTCODE => 7,
        self::COL_UNKNOWN_1 => 8,
        self::COL_YEAR_MFR => 4,
        self::COL_UNKNOWN_2 => 9,
        self::COL_GROSS_WEIGHT => 5,
    ];

    private $regex;

    public function __construct()
    {
        $this->regex = "/^";
        foreach (self::COLUMN_WIDTHS as $name => $length) {
            $this->regex .= "(?<$name>.{{$length}})";
        }
        $this->regex .= "/";
    }

    public function getDataAndOptions(FormInterface $form) {
        /** @var UploadedFile $file */
        $file = $form->get('file')->getData();

        return [
            'surveyOptions' => $this->getAggregateSurveyOptionsAndValidate($form),
            'importData' => $this->getDataFromFile($file),
            'filename' => $this->getOriginalFilename($file),
        ];
    }

    protected function getOriginalFilename(?UploadedFile $file) {
        if (!$file) {
            return null;
        }
        return pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
    }

    protected function getAggregateSurveyOptionsAndValidate(FormInterface $form)
    {
        $autoDetectedSurveyOptions = $this->getAutoDetectedSurveyOptions($form);
        $formSurveyOptions = $this->getFormSurveyOptions($form);
        $aggregateSurveyOptions = array_merge($autoDetectedSurveyOptions, array_filter($formSurveyOptions, function($v){return !is_null($v);}));

        $optionsForm = $form->get('survey_options');

        // Survey Period Start
        if (!isset($aggregateSurveyOptions['surveyPeriodStart'])) {
            $optionsForm->get('surveyPeriodStart')->addError(new FormError('The survey start date could not be auto-detected from the filename. Please enter a date.'));
        }

        // is Northern Ireland
        if (!isset($aggregateSurveyOptions['isNorthernIreland'])) {
            $optionsForm->get('isNorthernIreland')->addError(new FormError('The survey region could not be auto-detected from the filename. Please select a region.'));
        } else if (($autoDetectedSurveyOptions['isNorthernIreland'] ?? null)
                !== $aggregateSurveyOptions['isNorthernIreland']) {
            $aggregateSurveyOptions['overriddenRegion'] = true;
        }

        return $aggregateSurveyOptions;
    }

    protected function getFormSurveyOptions(FormInterface $form) {
        return array_intersect_key($form->getData(), array_fill_keys(['surveyPeriodStart', 'isNorthernIreland'], 1));
    }

    protected function getAutoDetectedSurveyOptions(FormInterface $form)
    {
        $originalFilename = $this->getOriginalFilename($form->get('file')->getData());

        $regex = '/^csrgt_output(_(?<ni>ni))?_surveyweek_(?<week>\d{1,2})_(?<gendate>\d{12})\d{2}$/';
        if (preg_match($regex, $originalFilename, $matches)) {
            $matches = array_intersect_key($matches, array_fill_keys(['ni', 'week', 'gendate'], 0));
            $isNI = strtolower($matches['ni']) === 'ni';
            $week = intval($matches['week']);
            $fileGenDate = new \DateTime($matches['gendate']);
            $year = ($week < $fileGenDate->format('W'))
                ? (clone $fileGenDate)->modify('+1 year')->format('Y')
                : $fileGenDate->format('Y');
            return [
                'isNorthernIreland' => $isNI,
                'surveyPeriodStart' => (new \DateTime('midnight'))->setISODate($year, $week),
            ];
        }

        return [];
    }

    public function getDataFromFile(?UploadedFile $file)
    {
        if (!$file) {
            return null;
        }
        $surveyData = [];
        $handle = fopen($file->getRealPath(), "r");
        while(!feof($handle)){
            $line = fgets($handle);
            if (empty($line)) continue;
            if ($lineData = $this->parseLine($line)) $surveyData[] = $lineData;
        }
        return $surveyData;
    }

    protected function parseLine($line)
    {
        if (preg_match($this->regex, $line, $matches)) {
            $matches = array_intersect_key($matches, self::COLUMN_WIDTHS);
            $matches = array_map('trim', $matches);
            return $matches;
        }
        return null;
    }

    public function createSurvey($surveyOptions, $surveyData)
    {
        $normalizer = new ObjectNormalizer();
        /** @var Survey $survey */
        $survey = $normalizer->denormalize($surveyOptions, Survey::class);
        return $survey
            ->setRegistrationMark($surveyData[self::COL_REG_MARK])
            ->setInvitationAddress($this->createAddress($surveyData))
            ;
    }

    protected function createAddress($surveyData)
    {
        return (new LongAddress())
            ->setLine1($surveyData[self::COL_ADDRESS_1])
            ->setLine2($surveyData[self::COL_ADDRESS_2])
            ->setLine3($surveyData[self::COL_ADDRESS_3])
            ->setLine4($surveyData[self::COL_ADDRESS_4])
            ->setLine5($surveyData[self::COL_ADDRESS_5])
            ->setLine6($surveyData[self::COL_ADDRESS_6])
            ->setPostcode($surveyData[self::COL_POSTCODE])
            ;
    }

}