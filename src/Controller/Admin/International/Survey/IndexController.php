<?php


namespace App\Controller\Admin\International\Survey;


use App\ListPage\International\SurveyListPage;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/irhs/surveys")
 * Class IndexController
 * @package App\Controller\Admin\International\Survey
 */
class IndexController extends AbstractController
{
    /**
     * @Route("")
     */
    public function index(SurveyListPage $listPage, Request $request): Response
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
     * @Route("")
     * @Template("admin/international/surveys/view.html.twig")
     */
    public function view()
    {
        return [];
    }
}