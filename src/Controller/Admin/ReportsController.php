<?php

namespace App\Controller\Admin;

use App\Form\Admin\ReportFilterType;
use App\Repository\Domestic\SurveyRepository as DomesticSurveyRepository;
use App\Repository\International\SurveyRepository as InternationalSurveyRepository;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/reports", name="admin_reports_")
 */
class ReportsController extends AbstractController
{
    /**
     * @Route("", name="dashboard")
     */
    public function dashboard(): Response
    {
        return $this->render('admin/report/dashboard.html.twig');
    }

    /**
     * @Route("/state/{type}", name="state_type", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs"})
     * @Route("/state/{type}/{year}/{quarter}", name="state_full", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs", "year": "\d{4}", "quarter": "1|2|3|4"})
     */
    public function stateReports(DomesticSurveyRepository $domesticRepo, InternationalSurveyRepository $interationalRepo, Request $request, string $type=null, int $year=null, int $quarter=null): Response
    {
        $type = $type ?? ReportFilterType::TYPE_CSRGT;

        if (!$year || !$quarter) {
            [$quarter, $year] = DomesticWeekNumberHelper::getQuarterAndYear(new \DateTime());

            return new RedirectResponse($this->generateUrl('admin_reports_state_full', [
                'type' => $type,
                'year' => $year,
                'quarter' => $quarter
            ]));
        }

        [$start, $end] = DomesticWeekNumberHelper::getDateRangeForYearAndQuarter($year, $quarter);

        [$intMinYear] = $interationalRepo->getMinimumAndMaximumYear();
        [$domMinYear] = $domesticRepo->getMinimumAndMaximumYear();
        $minYear = min($domMinYear, $intMinYear);

        $current = [
            'year' => $year,
            'quarter' => $quarter,
            'type' => $type,
        ];

        $form = $this->createForm(ReportFilterType::class, $current, [
            'minYear' => $minYear,
        ]);

        if ($request->getMethod() === Request::METHOD_POST) {
            $form->handleRequest($request);
            if ($form->isSubmitted()) {
                $data = $form->getData();
                $year = $data['year'];
                $quarter = $data['quarter'];
                $type = $data['type'];

                return new RedirectResponse($this->generateUrl('admin_reports_state_full', [
                    'type' => $type,
                    'year' => $year,
                    'quarter' => $quarter,
                ]));
            }
        }

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $domesticRepo->getStateReportStats(null, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $domesticRepo->getStateReportStats(false, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $domesticRepo->getStateReportStats(true, $start, $end);
                break;
            case ReportFilterType::TYPE_IRHS:
                $stats = $interationalRepo->getStateReportStats($start, $end);
                break;
            default:
                // Will never get here, but this will make the IDE happy with regards to $stats being uninitialised
                throw new NotFoundHttpException('Invalid report type');
        }

        return $this->render('admin/report/state.html.twig', array_merge($current, [
            'stats' => $stats,
            'form' => $form->createView(),
            'typeLabel' => $this->getTypeLabel($type),
        ]));
    }

    protected function getTypeLabel(string $type): string
    {
        return array_flip(ReportFilterType::CHOICE_TYPES)[$type];
    }
}
