<?php


namespace App\Utility;


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

    public function getDataFromFile($filepath)
    {
        $surveyData = [];
        $handle = fopen($filepath, "r");
        while(!feof($handle)){
            $line = fgets($handle);
            if (empty($line)) continue;
            $surveyData[] = $this->parseLine($line);
        }
        return $surveyData;
    }

    protected function parseLine($line)
    {
        preg_match($this->regex, $line, $matches);
        $matches = array_intersect_key($matches, self::COLUMN_WIDTHS);
        $matches = array_map('trim', $matches);
        return $matches;
    }

    protected function createSurvey($columns)
    {

    }
}