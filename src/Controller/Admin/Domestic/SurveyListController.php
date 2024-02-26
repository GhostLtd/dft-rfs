<?php

namespace App\Controller\Admin\Domestic;

use App\ListPage\Domestic\SurveyListPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SurveyListController extends AbstractController
{
    #[Route(path: '/csrgt/surveys-{type}/', name: SurveyController::LIST_ROUTE, requirements: ['type' => 'gb|ni'])]
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
            'form' => $listPage->getFiltersForm(),
        ]);
    }
}
