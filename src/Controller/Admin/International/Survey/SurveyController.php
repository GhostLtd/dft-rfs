<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Survey;
use App\ListPage\International\SurveyListPage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/surveys")
 */
class SurveyController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_international_survey_';
    public const LIST_ROUTE = self::ROUTE_PREFIX.'list';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'view';

    /**
     * @Route("", name=self::LIST_ROUTE)
     */
    public function list(SurveyListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/international/surveys/list.html.twig', [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }

    /**
     * @Route("/view/{surveyId}", name=self::VIEW_ROUTE)
     * @Entity("survey", expr="repository.findWithVehiclesAndTrips(surveyId)")
     */
    public function view(Survey $survey): Response
    {
        return $this->render('admin/international/surveys/view.html.twig', [
            'survey' => $survey,
        ]);
    }
}