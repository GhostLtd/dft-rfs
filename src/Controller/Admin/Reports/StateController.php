<?php

namespace App\Controller\Admin\Reports;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Form\Admin\ReportFilterType;
use App\Utility\StateReportHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class StateController extends AbstractReportsController
{
    #[Route(path: '/state/{type}', name: 'state_type', requirements: ['type' => 'csrgt|csrgt-ni|csrgt-gb|irhs|pre-enquiry|roro'])]
    public function stateReportsDefaults(string $type): Response
    {
        return $this->handleRedirect($type);
    }

    #[Route(path: '/state/{type}/{year}', name: 'state_yearly_full', requirements: ['type' => 'pre-enquiry|roro', 'year' => '\d{4}'])]
    public function yearlyStateReports(StateReportHelper $stateReportHelper, Request $request, string $type, int $year): Response
    {
        [$start, $end] = $this->getDateRangeForYear($year);

        $form = $this->getReportsYearlyFilterForm($request, $year, $type);

        if ($form->isSubmitted()) {
            return $this->getRedirect($form->getData());
        }

        return $this->stateReports($stateReportHelper, $form, $type, $start, $end);
    }

    #[Route(path: '/state/{type}/{year}/{quarter}', name: 'state_full', requirements: ['type' => 'csrgt|csrgt-ni|csrgt-gb|irhs|roro', 'year' => '\d{4}', 'quarter' => '1|2|3|4'])]
    public function quarterlyStateReports(StateReportHelper $stateReportHelper, Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = $this->getDateRangeForYearAndQuarter($type, $year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type);

        if ($form->isSubmitted()) {
            return $this->getRedirect($form->getData());
        }

        return $this->stateReports($stateReportHelper, $form, $type, $start, $end);
    }

    protected function stateReports(StateReportHelper $stateReportHelper, FormInterface $form, string $type, \DateTime $start, \DateTime $end): Response
    {
        $excludeFromTotals = [
            DomesticSurvey::STATE_REISSUED,
        ];

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $this->domesticReportsHelper->getStateReportStats(null, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $this->domesticReportsHelper->getStateReportStats(false, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $this->domesticReportsHelper->getStateReportStats(true, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_IRHS:
                $stats = $this->internationalReportsHelper->getStateReportStats($start, $end);
                break;
            case ReportFilterType::TYPE_PRE_ENQUIRY:
                $stats = $this->preEnquiryReportsHelper->getStateReportStats($start, $end);
                break;
            case ReportFilterType::TYPE_RORO:
                $stats = $this->roRoReportsHelper->getStateReportStats($start, $end);
                break;
        }

        return $this->render('admin/report/state.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
            'stateMappings' => $stateReportHelper->getStateReportMappingsByColumn($type),
            'excludeFromTotals' => $excludeFromTotals,
        ]));
    }

    #[\Override]
    protected function getRedirect(array $data): RedirectResponse
    {
        switch($data['type'] ?? null) {
            case ReportFilterType::TYPE_CSRGT:
            case ReportFilterType::TYPE_CSRGT_GB:
            case ReportFilterType::TYPE_CSRGT_NI:
            case ReportFilterType::TYPE_IRHS:
                $data['quarter'] ??= 1;
                return new RedirectResponse($this->generateUrl('admin_reports_state_full', $data));

            case ReportFilterType::TYPE_PRE_ENQUIRY:
            case ReportFilterType::TYPE_RORO:
                unset($data['quarter']);
                return new RedirectResponse($this->generateUrl('admin_reports_state_yearly_full', $data));
        }

        throw new \RuntimeException('Invalid type');
    }
}
