<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use App\Utility\International\WeekNumberHelper as InternationalWeekNumberHelper;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ApprovalsController extends AbstractReportsController
{
    #[Route(path: '/approvals/{type}', name: 'approvals_type', requirements: ['type' => 'csrgt|csrgt-ni|csrgt-gb|irhs|pre-enquiry|roro'])]
    public function approvalsReportsDefaults(string $type): Response
    {
        return $this->handleRedirect($type);
    }

    #[Route(path: '/approvals/{type}/{year}/{quarter}', name: 'approvals_full', requirements: ['type' => 'csrgt|csrgt-ni|csrgt-gb|irhs|pre-enquiry|roro', 'year' => '\d{4}', 'quarter' => '1|2|3|4'])]
    public function quarterlyApprovalsReport(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = $this->getDateRangeForYearAndQuarter($type, $year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type);

        if ($form->isSubmitted()) {
            return $this->getRedirect($form->getData());
        }

        return $this->approvalsReports($form, $type, $start, $end);
    }

    protected function approvalsReports(FormInterface $form, string $type, \DateTime $start, \DateTime $end): Response
    {

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
            case ReportFilterType::TYPE_PRE_ENQUIRY:
                $stats = $this->auditLogRepository->getPreEnquiryApprovalReportStats($start, $end);
                break;
            case ReportFilterType::TYPE_RORO:
                $stats = $this->auditLogRepository->getRoRoApprovalReportStats($start, $end);
                break;
        }

        return $this->render('admin/report/approvals.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }

    #[\Override]
    public function getRedirect(array $data): RedirectResponse
    {
        switch($data['type'] ?? null) {
            case ReportFilterType::TYPE_CSRGT:
            case ReportFilterType::TYPE_CSRGT_GB:
            case ReportFilterType::TYPE_CSRGT_NI:
            case ReportFilterType::TYPE_IRHS:
            case ReportFilterType::TYPE_PRE_ENQUIRY:
            case ReportFilterType::TYPE_RORO:
                $data['quarter'] ??= 1;
                return new RedirectResponse($this->generateUrl('admin_reports_approvals_full', $data));
        }

        throw new \RuntimeException('Invalid type');
    }
}
