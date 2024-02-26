<?php

namespace App\Controller\Admin\RoRo;

use App\ListPage\RoRo\SurveyListPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SurveyListController extends AbstractController
{
    #[Route("/roro", name: "admin_roro_surveys_list")]
    public function list(SurveyListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        $listPageData = $listPage->getData();

        return $this->render('admin/roro/surveys/list.html.twig', [
            'data' => $listPageData,
            'form' => $listPage->getFiltersForm(),
        ]);
    }
}
