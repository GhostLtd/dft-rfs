<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PossessionController extends AbstractReportsController
{
    #[Route(path: '/possession/{type}', name: 'possession_type', requirements: ['type' => 'csrgt|csrgt-ni|csrgt-gb'])]
    public function possessionReportsDefaults(string $type): Response
    {
        return $this->handleRedirect($type);
    }

    #[Route(path: '/possession/{type}/{year}/{quarter}', name: 'possession_full', requirements: ['type' => 'csrgt|csrgt-ni|csrgt-gb', 'year' => '\d{4}', 'quarter' => '1|2|3|4'])]
    public function possessionReports(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = $this->getDateRangeForYearAndQuarter($type, $year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type, [ReportFilterType::TYPE_IRHS, ReportFilterType::TYPE_PRE_ENQUIRY, ReportFilterType::TYPE_RORO]);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_possession_full', $form->getData()));
        }

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $this->domesticReportsHelper->getPossessionReportStats(null, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $this->domesticReportsHelper->getPossessionReportStats(false, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $this->domesticReportsHelper->getPossessionReportStats(true, $start, $end);
                break;
        }

        return $this->render('admin/report/possession.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }

    #[\Override]
    protected function getRedirect(array $data): RedirectResponse
    {
        switch($data['type'] ?? null) {
            case ReportFilterType::TYPE_CSRGT:
            case ReportFilterType::TYPE_CSRGT_GB:
            case ReportFilterType::TYPE_CSRGT_NI:
                $data['quarter'] ??= 1;
                return new RedirectResponse($this->generateUrl('admin_reports_possession_full', $data));
        }

        throw new \RuntimeException('Invalid type');
    }
}
