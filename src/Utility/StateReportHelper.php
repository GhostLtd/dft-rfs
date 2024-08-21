<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\SurveyStateInterface;
use App\Form\Admin\ReportFilterType;
use Symfony\Component\Routing\RouterInterface;

class StateReportHelper
{
    public function __construct(protected RouterInterface $router)
    {}

    public function getStateReportMappingsByColumn(string $type): array {
        $mappings = [];

        $isDomestic = in_array($type, [ReportFilterType::TYPE_CSRGT, ReportFilterType::TYPE_CSRGT_GB, ReportFilterType::TYPE_CSRGT_NI]);
        $isRoro = $type === ReportFilterType::TYPE_RORO;

        $columns = array_merge(
            [
                SurveyStateInterface::STATE_INVITATION_SENT,
                SurveyStateInterface::STATE_IN_PROGRESS,
                SurveyStateInterface::STATE_CLOSED,
            ],
            ($isDomestic ? [DomesticSurvey::STATE_REISSUED] : []),
            ($isDomestic || $isRoro ? [SurveyStateInterface::STATE_APPROVED,] : []),
            ($isRoro ? [] : [SurveyStateInterface::STATE_REJECTED]),
        );

        foreach($columns as $column) {
            $mappings[$column] = [$column];
        }

        foreach($this->getStateReportMergeMappings() as $from => $to) {
            if (array_key_exists($to, $mappings)) {
                $mappings[$to][] = $from;
            }
        }

        return $mappings;
    }

    public function getStateReportMergeMappings(): array {
        return [
            SurveyStateInterface::STATE_NEW => SurveyStateInterface::STATE_INVITATION_SENT,
            SurveyStateInterface::STATE_INVITATION_PENDING => SurveyStateInterface::STATE_INVITATION_SENT,
            SurveyStateInterface::STATE_INVITATION_FAILED => SurveyStateInterface::STATE_REJECTED,
        ];
    }
}