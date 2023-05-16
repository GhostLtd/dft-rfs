<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\SurveyStateInterface;
use App\Form\Admin\ReportFilterType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class StateReportHelper
{
    protected RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function getStateReportMappingsByColumn(string $type): array {
        $mappings = [];

        $isDomestic = !in_array($type, [ReportFilterType::TYPE_IRHS, ReportFilterType::TYPE_PRE_ENQUIRY]);
        $columns = array_merge(
            [
                SurveyStateInterface::STATE_INVITATION_SENT,
                SurveyStateInterface::STATE_IN_PROGRESS,
                SurveyStateInterface::STATE_CLOSED,
            ],
            ($isDomestic ? [DomesticSurvey::STATE_REISSUED] : []),
            [
                SurveyStateInterface::STATE_APPROVED,
                SurveyStateInterface::STATE_REJECTED,
//                SurveyStateInterface::STATE_EXPORTED,
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
            SurveyStateInterface::STATE_NEW => SurveyStateInterface::STATE_INVITATION_SENT,
            SurveyStateInterface::STATE_INVITATION_PENDING => SurveyStateInterface::STATE_INVITATION_SENT,
//            SurveyStateInterface::STATE_EXPORTING => SurveyStateInterface::STATE_EXPORTED,
            SurveyStateInterface::STATE_INVITATION_FAILED => SurveyStateInterface::STATE_REJECTED,
        ];
    }

    public function getFullRedirect(array $data): RedirectResponse
    {
        switch($data['type'] ?? null) {
            case ReportFilterType::TYPE_CSRGT:
            case ReportFilterType::TYPE_CSRGT_GB:
            case ReportFilterType::TYPE_CSRGT_NI:
            case ReportFilterType::TYPE_IRHS:
                $data['quarter'] ??= 1;

                return new RedirectResponse($this->router->generate('admin_reports_state_full', $data));
            case ReportFilterType::TYPE_PRE_ENQUIRY:
                return new RedirectResponse($this->router->generate('admin_reports_state_pre_enquiry_full', $data));
        }

        throw new \RuntimeException('Invalid type');
    }

    public function redirectToCurrent(string $type)
    {
        switch($data['type'] ?? null) {
            case ReportFilterType::TYPE_CSRGT:
            case ReportFilterType::TYPE_CSRGT_GB:
            case ReportFilterType::TYPE_CSRGT_NI:
            case ReportFilterType::TYPE_IRHS:
                $data['quarter'] ??= 1;

                return new RedirectResponse($this->router->generate('admin_reports_state_full', $data));
            case ReportFilterType::TYPE_PRE_ENQUIRY:
                return new RedirectResponse($this->router->generate('admin_reports_state_pre_enquiry_full', $data));
        }

        throw new \RuntimeException('Invalid type');
    }
}