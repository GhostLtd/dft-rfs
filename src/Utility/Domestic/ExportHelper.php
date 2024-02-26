<?php

namespace App\Utility\Domestic;

use App\Utility\AbstractExportHelper;

class ExportHelper extends AbstractExportHelper
{
    #[\Override]
    public function getPrefix(): string
    {
        return 'csrgt-export/csrgt';
    }

    #[\Override]
    public function getReportQuarterHelperClass(): string
    {
        return WeekNumberHelper::class;
    }
}