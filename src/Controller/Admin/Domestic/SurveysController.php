<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\BlameLog\BlameLog;
use App\Entity\Domestic\Survey;
use App\ListPage\Domestic\SurveyListPage;
use App\Repository\BlameLogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt", name="admin_domestic_")
 */
class SurveysController extends AbstractController
{
    /**
     * @Route("/surveys/{type}", name="surveys", requirements={"type": "gb|ni"})
     */
    public function list(SurveyListPage $listPage, Request $request, string $type): Response
    {
        $listPage
            ->setType($type)
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

    /**
     * @Route("/survey/view/{survey}", name="surveydetails")
     */
    public function viewDetails(Survey $survey): Response
    {
        return $this->render('admin/domestic/surveys/view.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @param EntityManagerInterface $blameLogEntityManager
     * @param $type
     * @param Survey $survey
     * @return Response
     * @Route("/view/{survey}/audit-log", name="surveylogs")
     */
    public function viewLog(EntityManagerInterface $blameLogEntityManager, Survey $survey): Response
    {
        $blameLogRepository = $blameLogEntityManager->getRepository(BlameLog::class);
        return $this->render('admin/domestic/surveys/log.html.twig', [
            'survey' => $survey,
            'log' => $blameLogRepository->getAllLogsForEntity($survey),
        ]);
    }

}
