<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ActivityStatusController extends AbstractReportsController
{
    /**
     * @Route("/activity-status/irhs", name="activity_status_type", requirements={"type": "irhs"})
     */
    public function activityStatusDefaults(): Response
    {
        return $this->redirectToCurrentQuarterAndYear('irhs', 'admin_reports_activity_status_full');
    }

    /**
     * @Route("/activity-status/{type}/{year}/{quarter}", name="activity_status_full", requirements={"type": "irhs", "year": "\d{4}", "quarter": "1|2|3|4"})
     */
    public function activityStatusReports(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = self::getDateRangeForYearAndQuarter($type, $year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type, [ReportFilterType::TYPE_CSRGT, ReportFilterType::TYPE_CSRGT_GB, ReportFilterType::TYPE_CSRGT_NI]);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_activity_status_full', $form->getData()));
        }

        switch($type) {
            case ReportFilterType::TYPE_IRHS:
                $stats = $this->internationalRepo->getActivityStatusReportStats($start, $end);
                break;
        }

        return $this->render('admin/report/activity-status.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }
}
