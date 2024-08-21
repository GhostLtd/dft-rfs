<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ActivityStatusController extends AbstractReportsController
{
    #[Route(path: '/activity-status/{type}}', name: 'activity_status_type', requirements: ['type' => 'irhs'])]
    public function activityStatusDefaults(string $type): Response
    {
        return $this->handleRedirect($type);
    }

    #[Route(path: '/activity-status/{type}/{year}/{quarter}', name: 'activity_status_full', requirements: ['type' => 'irhs', 'year' => '\d{4}', 'quarter' => '1|2|3|4'])]
    public function activityStatusReports(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = $this->getDateRangeForYearAndQuarter($type, $year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type, [ReportFilterType::TYPE_CSRGT, ReportFilterType::TYPE_CSRGT_GB, ReportFilterType::TYPE_CSRGT_NI, ReportFilterType::TYPE_PRE_ENQUIRY]);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_activity_status_full', $form->getData()));
        }

        switch($type) {
            case ReportFilterType::TYPE_IRHS:
                $stats = $this->internationalReportsHelper->getActivityStatusReportStats($start, $end);
                break;
        }

        return $this->render('admin/report/activity-status.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }

    #[\Override]
    public function getRedirect(array $data): RedirectResponse
    {
        switch($data['type'] ?? null) {
            case ReportFilterType::TYPE_IRHS:
                $data['quarter'] ??= 1;
                return new RedirectResponse($this->generateUrl('admin_reports_activity_status_full', $data));
        }

        throw new \RuntimeException('Invalid type');
    }
}
