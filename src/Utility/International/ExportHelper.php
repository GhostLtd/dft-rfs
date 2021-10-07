<?php

namespace App\Utility\International;

use App\Utility\AbstractExportHelper;

class ExportHelper extends AbstractExportHelper
{
    public function getPrefix(): string
    {
        return 'irhs-export/irhs';
    }

    public function getReportQuarterHelperClass(): string
    {
        return ReportQuarterHelper::class;
    }
}