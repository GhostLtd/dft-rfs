<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PossessionController extends AbstractReportsController
{
    /**
     * @Route("/possession/{type}", name="possession_type", requirements={"type": "csrgt|csrgt-ni|csrgt-gb"})
     */
    public function possessionReportsDefaults(string $type): Response
    {
        return $this->redirectToCurrentQuarterAndYear($type, 'admin_reports_possession_full');
    }

    /**
     * @Route("/possession/{type}/{year}/{quarter}", name="possession_full", requirements={"type": "csrgt|csrgt-ni|csrgt-gb", "year": "\d{4}", "quarter": "1|2|3|4"})
     */
    public function possessionReports(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = self::getDateRangeForYearAndQuarter($type, $year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type, [ReportFilterType::TYPE_IRHS]);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_possession_full', $form->getData()));
        }

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $this->domesticRepo->getPossessionReportStats(null, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $this->domesticRepo->getPossessionReportStats(false, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $this->domesticRepo->getPossessionReportStats(true, $start, $end);
                break;
        }

        return $this->render('admin/report/possession.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }
}
