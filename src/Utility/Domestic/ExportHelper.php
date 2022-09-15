<?php

namespace App\Utility\Domestic;

use App\Utility\AbstractExportHelper;

class ExportHelper extends AbstractExportHelper
{
    public function getPrefix(): string
    {
        return 'csrgt-export/csrgt';
    }

    public function getReportQuarterHelperClass(): string
    {
        return WeekNumberHelper::class;
    }
}