<?php

namespace App\Controller\Admin\Domestic;

use App\ListPage\Domestic\SurveyListPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SurveyListController extends AbstractController
{
    /**
     * @Route("/csrgt/surveys-{type}/", requirements={"type": "gb|ni"}, name=SurveyController::LIST_ROUTE)
     */
    public function list(SurveyListPage $listPage, Request $request, string $type): Response
    {
        $listPage
            ->setIsNorthernIreland($type === 'ni')
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/domestic/surveys/list.html.twig', [
            'type' => $type,
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }
}