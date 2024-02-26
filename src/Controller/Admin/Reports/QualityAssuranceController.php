<?php

namespace App\Controller\Admin\Reports;

use App\Form\Admin\ReportFilterType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class QualityAssuranceController extends AbstractReportsController
{
    #[Route(path: '/quality-assurance/{type}', name: 'qa_type', requirements: ['type' => 'csrgt|csrgt-ni|csrgt-gb|irhs|pre-enquiry'])]
    public function qualityAssuranceReportsDefaults(string $type): Response
    {
        return $this->handleRedirect($type);
    }

    #[Route(path: '/quality-assurance/{type}/{year}/{quarter}', name: 'qa_full', requirements: ['type' => 'csrgt|csrgt-ni|csrgt-gb|irhs', 'year' => '\d{4}', 'quarter' => '1|2|3|4'])]
    public function quarterlyQualityAssuranceReports(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = $this->getDateRangeForYearAndQuarter($type, $year, $quarter);
        $form = $this->getReportsFilterForm($request, $year, $quarter, $type, [ReportFilterType::TYPE_PRE_ENQUIRY]);

        if ($form->isSubmitted()) {
            return $this->getRedirect($form->getData());
        }

        return $this->qualityAssuranceReports($form, $type, $start, $end);
    }

    protected function qualityAssuranceReports(FormInterface $form, string $type, \DateTime $start, \DateTime $end): Response
    {
        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $this->domesticReportsHelper->getQualityAssuranceReportStats(null, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $this->domesticReportsHelper->getQualityAssuranceReportStats(false, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $this->domesticReportsHelper->getQualityAssuranceReportStats(true, $start, $end);
                break;
            case ReportFilterType::TYPE_IRHS:
                $stats = $this->internationalReportsHelper->getQualityAssuranceReportStats($start, $end);
                break;
        }

        return $this->render('admin/report/quality-assurance.html.twig', array_merge($form->getData(), [
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
            case ReportFilterType::TYPE_IRHS:
                $data['quarter'] ??= 1;
                return new RedirectResponse($this->generateUrl('admin_reports_qa_full', $data));

            case ReportFilterType::TYPE_RORO:
                unset($data['quarter']);
                return new RedirectResponse($this->generateUrl('admin_reports_qa_yearly_full', $data));
        }

        throw new \RuntimeException('Invalid type');
    }
}
