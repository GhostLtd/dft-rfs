<?php

namespace App\Utility;

use App\Utility\Cleanup\QuarterHelperInterface;
use DateTime;

abstract class AbstractExportHelper
{
    abstract public function getPrefix(): string;
    abstract public function getQuarterHelper(): QuarterHelperInterface;

    public function getPossibleYearsAndQuarters(bool $includeCurrentQuarter = false): array
    {
        $possibilities = [];

        $reportQuarterHelperClass = $this->getQuarterHelper();
        [$currentQuarter, $currentYear] = $reportQuarterHelperClass->getQuarterAndYear(new DateTime());
        $lastYear = $currentYear - 1;

        for($quarter=($currentQuarter - ($includeCurrentQuarter ? 0 : 1)); $quarter>=1; $quarter--) {
            $possibilities[$currentYear.'-'.$quarter] = new ExportObject(null, $currentYear, $quarter);
        }

        for($quarter=4; $quarter>=1; $quarter--) {
            $possibilities[$lastYear.'-'.$quarter] = new ExportObject(null, $lastYear, $quarter);
        }

        return $possibilities;
    }

    protected function getName(string $year, string $quarter): string
    {
        return "{$this->getPrefix()}-export-{$year}-Q{$quarter}.sql";
    }
}
