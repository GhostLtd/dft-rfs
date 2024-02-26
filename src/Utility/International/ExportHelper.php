<?php

namespace App\Utility\International;

use App\Utility\AbstractExportHelper;

class ExportHelper extends AbstractExportHelper
{
    #[\Override]
    public function getPrefix(): string
    {
        return 'irhs-export/irhs';
    }

    #[\Override]
    public function getReportQuarterHelperClass(): string
    {
        return ReportQuarterHelper::class;
    }
}