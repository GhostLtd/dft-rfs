<?php

namespace App\Controller\Admin;

use App\Form\Admin\ReportFilterType;
use App\Repository\Domestic\SurveyRepository as DomesticSurveyRepository;
use App\Repository\International\SurveyRepository as InternationalSurveyRepository;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use App\Utility\International\WeekNumberHelper as InternationalWeekNumberHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("", name="admin_index")
     */
    public function index(DomesticSurveyRepository $domesticRepo, InternationalSurveyRepository $internationalRepo, PreEnquiryRepository $preEnquiryRepo): Response
    {
        $now = new \DateTime();

        [$domesticWeek, $domesticYear] = DomesticWeekNumberHelper::getWeekNumberAndYear($now);
        $internationalWeek = InternationalWeekNumberHelper::getWeekNumber($now);

        return $this->render('admin/index/index.html.twig', [
            'domesticCounts' => $domesticRepo->getCountsByStatus(),
            'internationalCounts' => $internationalRepo->getCountsByStatus(),
            'preEnquiryCounts' => $preEnquiryRepo->getCountsByStatus(),
            'domesticWeek' => $domesticWeek,
            'domesticYear' => $domesticYear,
            'internationalWeek' => $internationalWeek,
            'domesticGbInProgressCount' => $domesticRepo->getInProgressCount(false),
            'domesticGbOverdueCount' => $domesticRepo->getOverdueCount(false),
            'domesticNiInProgressCount' => $domesticRepo->getInProgressCount(true),
            'domesticNiOverdueCount' => $domesticRepo->getOverdueCount(true),
            'internationalInProgressCount' => $internationalRepo->getInProgressCount(),
            'internationalOverdueCount' => $internationalRepo->getOverdueCount(),
        ]);
    }

    /**
     * @Route("/reports", name="admin_reports")
     * @Route("/reports/{type}/{year}/{quarter}", name="admin_reports_type", requirements={"type": "csrgt|csrgt-ni|csrgt-gb|irhs"})
     */
    public function reports(DomesticSurveyRepository $domesticRepo, InternationalSurveyRepository $interationalRepo, Request $request, string $type=null, int $year=null, int $quarter=null): Response
    {
        $type = $type ?? ReportFilterType::TYPE_CSRGT;

        if (!$year || !$quarter) {
            [$quarter, $year] = DomesticWeekNumberHelper::getQuarterAndYear(new \DateTime());
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

                return new RedirectResponse($this->generateUrl('admin_reports_type', [
                    'type' => $type,
                    'year' => $year,
                    'quarter' => $quarter,
                ]));
            }
        }

        switch($type) {
            case ReportFilterType::TYPE_CSRGT:
                $stats = $domesticRepo->getReportStats(null, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_GB:
                $stats = $domesticRepo->getReportStats(false, $start, $end);
                break;
            case ReportFilterType::TYPE_CSRGT_NI:
                $stats = $domesticRepo->getReportStats(true, $start, $end);
                break;
            case ReportFilterType::TYPE_IRHS:
                $stats = $interationalRepo->getReportStats($start, $end);
                break;
            default:
                // Will never get here, but this will make the IDE happy with regards to $stats being uninitialised
                throw new NotFoundHttpException('Invalid report type');
        }

        return $this->render('admin/domestic/report/report.html.twig', array_merge($current, [
            'stats' => $stats,
            'form' => $form->createView(),
            'typeLabel' => array_flip(ReportFilterType::CHOICE_TYPES)[$type],
        ]));
    }
}
