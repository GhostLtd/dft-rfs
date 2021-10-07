<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QualityAssuranceController extends AbstractReportsController
{
    /**
     * @Route("/quality-assurance/{type}", name="qa_type", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs"})
     */
    public function qualityAssuranceReportsDefaults(string $type): Response
    {
        return $this->redirectToCurrentQuarterAndYear($type, 'admin_reports_qa_full');
    }

    /**
     * @Route("/quality-assurance/{type}/{year}/{quarter}", name="qa_full", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs", "year": "\d{4}", "quarter": "1|2|3|4"})
     */
    public function qualityAssuranceReports(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = self::getDateRangeForYearAndQuarter($type, $year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_qa_full', $form->getData()));
        }

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $this->domesticRepo->getQualityAssuranceReportStats(null, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $this->domesticRepo->getQualityAssuranceReportStats(false, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $this->domesticRepo->getQualityAssuranceReportStats(true, $start, $end);
                break;
            case ReportFilterType::TYPE_IRHS:
                $stats = $this->internationalRepo->getQualityAssuranceReportStats($start, $end);
                break;
        }

        return $this->render('admin/report/quality-assurance.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }
}
