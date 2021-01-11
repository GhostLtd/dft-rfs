<?php

namespace App\Controller\Admin\International\Survey;

use App\Entity\International\Survey;
use App\Entity\International\Vehicle;
use App\ListPage\International\SurveyListPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/surveys")
 */
class SurveyController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_international_survey_';
    public const LIST_ROUTE = self::ROUTE_PREFIX.'list';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'view';
    public const VEHICLE_ROUTE = self::ROUTE_PREFIX.'view_vehicle';

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
     * @Route("/view/{survey}", name=self::VIEW_ROUTE)
     */
    public function view(Survey $survey): Response
    {
        return $this->render('admin/international/surveys/view.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @Route("/view/{survey}/vehicle/{vehicle}", name=self::VEHICLE_ROUTE)
     */
    public function viewVehicle(Survey $survey, Vehicle $vehicle): Response
    {
        if ($vehicle->getSurveyResponse()->getId() !== $survey->getResponse()->getId()) {
            throw new NotFoundHttpException();
        }

        return $this->render('admin/international/surveys/view.html.twig', [
            'survey' => $survey,
            'vehicle' => $vehicle,
        ]);
    }
}