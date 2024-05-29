<?php

namespace App\Utility\Domestic;

use App\Utility\AbstractExportHelper;
use App\Utility\Quarter\CsrgtQuarterHelper;
use App\Utility\Cleanup\QuarterHelperInterface;

class ExportHelper extends AbstractExportHelper
{
    public function __construct(
        protected CsrgtQuarterHelper $quarterHelper,
    ) {}

    #[\Override]
    public function getPrefix(): string
    {
        return 'csrgt-export/csrgt';
    }

    #[\Override]
    public function getQuarterHelper(): QuarterHelperInterface
    {
        return $this->quarterHelper;
    }
}
