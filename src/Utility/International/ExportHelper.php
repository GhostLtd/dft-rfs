<?php

namespace App\Utility\International;

use App\Utility\AbstractExportHelper;
use App\Utility\Quarter\NaturalQuarterHelper;
use App\Utility\Cleanup\QuarterHelperInterface;

class ExportHelper extends AbstractExportHelper
{
    public function __construct(
        protected NaturalQuarterHelper $quarterHelper,
    ) {}

    #[\Override]
    public function getPrefix(): string
    {
        return 'irhs-export/irhs';
    }

    #[\Override]
    public function getQuarterHelper(): QuarterHelperInterface
    {
        return $this->quarterHelper;
    }
}
