<?php

namespace App\Controller\Admin;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Form\Admin\ReportFilterType;
use App\Repository\Domestic\SurveyRepository as DomesticSurveyRepository;
use App\Repository\International\SurveyRepository as InternationalSurveyRepository;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use App\Utility\StateReportHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reports", name="admin_reports_")
 */
class ReportsController extends AbstractController
{
    protected DomesticSurveyRepository $domesticRepo;
    protected InternationalSurveyRepository $internationalRepo;

    public function __construct(DomesticSurveyRepository $domesticRepo, InternationalSurveyRepository $internationalRepo)
    {
        $this->domesticRepo = $domesticRepo;
        $this->internationalRepo = $internationalRepo;
    }

    /**
     * @Route("", name="dashboard")
     */
    public function dashboard(): Response
    {
        return $this->render('admin/report/dashboard.html.twig');
    }

    /**
     * @Route("/state/{type}", name="state_type", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs"})
     */
    public function stateReportsDefaults(string $type): Response
    {
        return $this->redirectToCurrentQuarterAndYear($type, 'admin_reports_state_full');
    }

    /**
     * @Route("/possession/{type}", name="possession_type", requirements={"type": "csrgt|csrgt-ni|csrgt-gb"})
     */
    public function possessionReportsDefaults(string $type): Response
    {
        return $this->redirectToCurrentQuarterAndYear($type, 'admin_reports_possession_full');
    }

    /**
     * @Route("/activity-status/irhs", name="activity_status_type", requirements={"type": "irhs"})
     */
    public function activityStatusDefaults(): Response
    {
        return $this->redirectToCurrentQuarterAndYear('irhs', 'admin_reports_activity_status_full');
    }

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
        [$start, $end] = DomesticWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

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

    /**
     * @Route("/possession/{type}/{year}/{quarter}", name="possession_full", requirements={"type": "csrgt|csrgt-ni|csrgt-gb", "year": "\d{4}", "quarter": "1|2|3|4"})
     */
    public function possessionReports(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = DomesticWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

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

    /**
     * @Route("/state/{type}/{year}/{quarter}", name="state_full", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs", "year": "\d{4}", "quarter": "1|2|3|4"})
     */
    public function stateReports(Request $request, StateReportHelper $stateReportHelper, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = DomesticWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

        $form = $this->getReportsFilterForm($request, $year, $quarter, $type);

        if ($form->isSubmitted()) {
            return new RedirectResponse($this->generateUrl('admin_reports_state_full', $form->getData()));
        }

        $excludeFromTotals = [
            DomesticSurvey::STATE_REISSUED,
        ];

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $this->domesticRepo->getStateReportStats(null, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $this->domesticRepo->getStateReportStats(false, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $this->domesticRepo->getStateReportStats(true, $start, $end, $excludeFromTotals);
                break;
            case ReportFilterType::TYPE_IRHS:
                $stats = $this->internationalRepo->getStateReportStats($start, $end);
                break;
        }

        $isDomestic = $type !== ReportFilterType::TYPE_IRHS;

        return $this->render('admin/report/state.html.twig', array_merge($form->getData(), [
            'stats' => $stats ?? null,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
            'stateMappings' => $stateReportHelper->getStateReportMappingsByColumn($isDomestic),
            'excludeFromTotals' => $excludeFromTotals,
        ]));
    }


    /**
     * @Route("/activity-status/{type}/{year}/{quarter}", name="activity_status_full", requirements={"type": "irhs", "year": "\d{4}", "quarter": "1|2|3|4"})
     */
    public function activityStatusReports(Request $request, string $type, int $year, int $quarter): Response
    {
        [$start, $end] = DomesticWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

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

    protected function getTypeLabel(string $type): string
    {
        return array_flip(ReportFilterType::CHOICE_TYPES)[$type];
    }

    protected function redirectToCurrentQuarterAndYear(string $type, string $routeName): RedirectResponse
    {
        [$quarter, $year] = DomesticWeekNumberHelper::getQuarterAndYear(new \DateTime());

        return new RedirectResponse($this->generateUrl($routeName, [
            'type' => $type,
            'year' => $year,
            'quarter' => $quarter
        ]));
    }

    protected function getReportsFilterForm(Request $request, int $year, int $quarter, string $type, array $excludeChoices=[]): FormInterface
    {
        $current = [
            'year' => $year,
            'quarter' => $quarter,
            'type' => $type,
        ];

        [$intMinYear] = $this->internationalRepo->getMinimumAndMaximumYear();
        [$domMinYear] = $this->domesticRepo->getMinimumAndMaximumYear();
        $minYear = min($domMinYear, $intMinYear);

        $form = $this->createForm(ReportFilterType::class, $current, [
            'minYear' => $minYear,
            'excludeChoices' => $excludeChoices,
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
        }

        return $form;
    }
}
