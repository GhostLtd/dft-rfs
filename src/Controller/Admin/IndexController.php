<?php

namespace App\Controller\Admin;

use App\Repository\Domestic\SurveyRepository as DomesticSurveyRepository;
use App\Repository\International\SurveyRepository as InternationalSurveyRepository;
use App\Repository\PreEnquiry\PreEnquiryRepository;
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
        return $this->render('admin/index/index.html.twig', [
            'domesticCounts' => $domesticRepo->getCountsByStatus(),
            'internationalCounts' => $internationalRepo->getCountsByStatus(),
            'preEnquiryCounts' => $preEnquiryRepo->getCountsByStatus(),
        ]);
    }
}
