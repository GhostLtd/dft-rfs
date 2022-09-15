<?php

namespace App\Controller\Admin\Reports;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Form\Admin\ReportFilterType;
use App\Utility\StateReportHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class StateController extends AbstractReportsController
{
    /**
     * @Route("/state/{type}", name="state_type", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs|pre-enquiry"})
     */
    public function stateReportsDefaults(string $type): Response
    {
        if ($type === ReportFilterType::TYPE_PRE_ENQUIRY) {
            return new RedirectResponse('admin_reports_state_pre_enquiry_full');
        }

        return $this->redirectToCurrentQuarterAndYear($type, 'admin_reports_state_full');
    }

    /**
     * @Route("/state/{type}/{year}/{quarter}", name="state_full", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs", "year": "\d{4}", "quarter": "1|2|3|4"})
     */
    public function stateReports(Request $request, StateReportHelper $stateReportHelper, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = self::getDateRangeForYearAndQuarter($type, $year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type);

        if ($form->isSubmitted()) {
            return $stateReportHelper->getFullRedirect($form->getData());
        }

        $excludeFromTotals = [
            DomesticSurvey::STATE_REISSUED,
        ];

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $this->domesticRepo->getStateReportStats(null, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $this->domesticRepo->getStateReportStats(false, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $this->domesticRepo->getStateReportStats(true, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_IRHS:
                $stats = $this->internationalRepo->getStateReportStats($start, $end);
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
}
