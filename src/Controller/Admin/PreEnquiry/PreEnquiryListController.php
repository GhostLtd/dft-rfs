<?php

namespace App\Controller\Admin\PreEnquiry;

use App\ListPage\PreEnquiry\PreEnquiryListPage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PreEnquiryListController extends AbstractController
{
    /**
     * @Route("/irhs/pre-enquiry", name=PreEnquiryController::LIST_ROUTE)
     */
    public function list(PreEnquiryListPage $listPage, Request $request): Response
    {
        $listPage
            ->handleRequest($request);

        if ($listPage->isClearClicked()) {
            return new RedirectResponse($listPage->getClearUrl());
        }

        return $this->render('admin/pre-enquiry/list.html.twig', [
            'data' => $listPage->getData(),
            'form' => $listPage->getFiltersForm()->createView(),
        ]);
    }
}