<?php

namespace App\Controller\Admin;

use App\Repository\Domestic\SurveyRepository as DomesticSurveyRepository;
use App\Repository\International\SurveyRepository as InternationalSurveyRepository;
use App\Repository\PreEnquiry\PreEnquiryRepository;
use App\Utility\Domestic\WeekNumberHelper as DomesticWeekNumberHelper;
use App\Utility\International\WeekNumberHelper as InternationalWeekNumberHelper;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class IndexController extends AbstractController
{
    /**
     * @Route("", name="admin_index")
     */
    public function index(DomesticSurveyRepository $domesticRepo, InternationalSurveyRepository $internationalRepo, PreEnquiryRepository $preEnquiryRepo): Response
    {
        $now = new \DateTime();

        [$domesticWeek, $domesticYear] = DomesticWeekNumberHelper::getYearlyWeekNumberAndYear($now);
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
}
