<?php

namespace App\Utility\Quarter;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Form\Admin\ReportFilterType;
use App\Utility\Cleanup\QuarterHelperInterface;

class QuarterHelperProvider
{
    public function __construct(
        protected CsrgtQuarterHelper   $csrgtQuarterHelper,
        protected IrhsQuarterHelper    $irhsQuarterHelper,
        protected NaturalQuarterHelper $naturalQuarterHelper,
    ) {}

    public function getQuarterHelperForDataCleanupBySurveyClass(string $className): QuarterHelperInterface
    {
        return match($className) {
            DomesticSurvey::class =>
                $this->csrgtQuarterHelper,

            InternationalSurvey::class,
            PreEnquiry::class,
            RoRoSurvey::class =>
                $this->naturalQuarterHelper,
        };
    }

    public function getQuarterHelperByReportClass(string $className): QuarterHelperInterface
    {
        // Reports are listed with weekly results alongside the relevant week numbers

        // CSRGT uses Monday-based weeks which reset yearly (i.e. first week of each year is week year 1)
        // IRHS uses Sunday-based that are contiguous (i.e. each year adds 52/53 to the week count which originates sometime in 1991)
        // PRE/RORO don't have many reports, but also use the CSRGT week numbering scheme for reports

        // N.B. This is different to exports where IRHS instead uses NaturalQuarterHelper, and PRE/RORO export by month

        return match($className) {
            ReportFilterType::TYPE_CSRGT,
            ReportFilterType::TYPE_CSRGT_GB,
            ReportFilterType::TYPE_CSRGT_NI,
            ReportFilterType::TYPE_PRE_ENQUIRY,
            ReportFilterType::TYPE_RORO =>
                $this->csrgtQuarterHelper,

            ReportFilterType::TYPE_IRHS =>
                $this->irhsQuarterHelper,
        };
    }
}