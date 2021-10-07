<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use App\Utility\International\WeekNumberHelper as InternationalWeekNumberHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ApprovalsController extends AbstractReportsController
{
    /**
     * @Route("/approvals/{type}", name="approvals_type", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs"})
     */
    public function approvalsReportsDefaults(string $type): Response
    {
        return $this->redirectToCurrentQuarterAndYear($type, 'admin_reports_approvals_full');
    }

    /**
     * @Route("/approvals/{type}/{year}/{quarter}", name="approvals_full",
     *     requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs", "year": "\d{4}", "quarter": "1|2|3|4"}
     * )
     */
    public function approvalsReport(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = $type === 'irhs'
            ? InternationalWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter)
            : DomesticWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_approvals_full', $form->getData()));
        }

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $this->auditLogRepository->getDomesticApprovalReportStats($start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $this->auditLogRepository->getDomesticApprovalReportStats($start, $end, false);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $this->auditLogRepository->getDomesticApprovalReportStats($start, $end, true);
                break;
            case ReportFilterType::TYPE_IRHS:
                $stats = $this->auditLogRepository->getInternationalApprovalReportStats($start, $end);
                break;
        }

        return $this->render('admin/report/approvals.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }
}
