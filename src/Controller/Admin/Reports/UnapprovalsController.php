<?php

namespace App\Controller\Admin\Reports;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Form\Admin\ReportFilterType;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use App\Utility\International\WeekNumberHelper as InternationalWeekNumberHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UnapprovalsController extends AbstractReportsController
{
    /**
     * @Route("/unapprovals/{type}", name="unapprovals_type", requirements={"type": "csrgt|irhs"})
     */
    public function unapprovalsReportsDefaults(string $type): Response
    {
        return $this->redirectToCurrentQuarterAndYear($type, 'admin_reports_unapprovals_full');
    }

    /**
     * @Route("/unapprovals/{type}/{year}/{quarter}", name="unapprovals_full",
     *     requirements={"type": "csrgt|irhs", "year": "\d{4}", "quarter": "1|2|3|4"}
     * )
     */
    public function unApprovalsReport(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = $type === 'irhs'
            ? InternationalWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter)
            : DomesticWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type, [ReportFilterType::TYPE_CSRGT_GB, ReportFilterType::TYPE_CSRGT_NI]);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_unapprovals_full', $form->getData()));
        }

        $stats = $this->auditLogRepository->getUnapprovalReportStats(
            $type === 'irhs' ? InternationalSurvey::class : DomesticSurvey::class,
            $start, $end
        );

        return $this->render('admin/report/unapprovals.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }
}
