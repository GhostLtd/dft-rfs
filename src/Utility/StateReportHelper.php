<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\SurveyInterface;

class StateReportHelper
{
    public function getStateReportMappingsByColumn(bool $isDomestic): array {
        $mappings = [];

        $columns = array_merge(
            [
                SurveyInterface::STATE_INVITATION_SENT,
                SurveyInterface::STATE_IN_PROGRESS,
                SurveyInterface::STATE_CLOSED,
            ],
            ($isDomestic ? [DomesticSurvey::STATE_REISSUED] : []),
            [
                SurveyInterface::STATE_APPROVED,
                SurveyInterface::STATE_REJECTED,
//                SurveyInterface::STATE_EXPORTED,
            ]);

        foreach($columns as $column) {
            $mappings[$column] = [$column];
        }

        foreach($this->getStateReportMergeMappings() as $from => $to) {
            $mappings[$to][] = $from;
        }

        return $mappings;
    }

    public function getStateReportMergeMappings(): array {
        return [
            SurveyInterface::STATE_NEW => SurveyInterface::STATE_INVITATION_SENT,
            SurveyInterface::STATE_INVITATION_PENDING => SurveyInterface::STATE_INVITATION_SENT,
//            SurveyInterface::STATE_EXPORTING => SurveyInterface::STATE_EXPORTED,
            SurveyInterface::STATE_INVITATION_FAILED => SurveyInterface::STATE_REJECTED,
        ];
    }
}