<?php

namespace App\Controller\Admin\Reports;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Form\Admin\ReportFilterType;
use App\Utility\Quarter\CsrgtQuarterHelper;
use App\Utility\Quarter\NaturalQuarterHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UnapprovalsController extends AbstractReportsController
{
    #[Route(path: '/unapprovals/{type}', name: 'unapprovals_type', requirements: ['type' => 'csrgt|irhs'])]
    public function unapprovalsReportsDefaults(string $type): Response
    {
        return $this->handleRedirect($type);
    }

    #[Route(path: '/unapprovals/{type}/{year}/{quarter}', name: 'unapprovals_full', requirements: ['type' => 'csrgt|irhs', 'year' => '\d{4}', 'quarter' => '1|2|3|4'])]
    public function unApprovalsReport(
        CsrgtQuarterHelper   $csrgtQuarterQuarterHelper,
        NaturalQuarterHelper $naturalQuarterHelper,
        Request              $request,
        string               $type,
        int                  $year,
        int                  $quarter
    ): Response
    {
        [$start, $end] = $type === 'irhs'
            ? $naturalQuarterHelper->getDateRangeForYearAndQuarter($year, $quarter)
            : $csrgtQuarterQuarterHelper->getDateRangeForYearAndQuarter($year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type, [ReportFilterType::TYPE_CSRGT_GB, ReportFilterType::TYPE_CSRGT_NI, ReportFilterType::TYPE_PRE_ENQUIRY]);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_unapprovals_full', $form->getData()));
        }

        $stats = $this->auditLogRepository->getUnapprovalReportStats(
            $type === 'irhs' ? InternationalSurvey::class : DomesticSurvey::class,
            $start, $end
        );

        return $this->render('admin/report/unapprovals.html.twig', array_merge($form->getData(), [
            'stats' => $stats,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }


    #[\Override]
    protected function getRedirect(array $data): RedirectResponse
    {
        switch ($data['type'] ?? null) {
            case ReportFilterType::TYPE_CSRGT:
            case ReportFilterType::TYPE_IRHS:
                $data['quarter'] ??= 1;
                return new RedirectResponse($this->generateUrl('admin_reports_unapprovals_full', $data));
        }

        throw new \RuntimeException('Invalid type');
    }
}
