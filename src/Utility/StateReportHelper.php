<?php

namespace App\Utility;

use App\Entity\SurveyInterface;

class StateReportHelper
{
    public function getStateReportMappingsByColumn(): array {
        $mappings = [];

        foreach([
                    SurveyInterface::STATE_INVITATION_SENT,
                    SurveyInterface::STATE_IN_PROGRESS,
                    SurveyInterface::STATE_CLOSED,
                    SurveyInterface::STATE_REJECTED,
                    SurveyInterface::STATE_EXPORTED,
                    SurveyInterface::STATE_APPROVED,
                ] as $column) {
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
            SurveyInterface::STATE_EXPORTING => SurveyInterface::STATE_EXPORTED,
            SurveyInterface::STATE_INVITATION_FAILED => SurveyInterface::STATE_REJECTED,
        ];
    }
}