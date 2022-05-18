<?php

namespace App\Utility;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\SurveyInterface;
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