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
 * @Route("/csrgt/{type}/surveys", name="admin_domestic_", requirements={"type": "gb|ni"})
 */
class SurveysController extends AbstractController
{
    /**
     * @Route("", name="surveys")
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
     * @Route("/view/{survey}", name="surveydetails")
     */
    public function viewDetails($type, Survey $survey): Response
    {
        if ($survey->getIsNorthernIreland() !== ($type === 'ni')) {
            throw new NotFoundHttpException();
        }

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
    public function viewLog(EntityManagerInterface $blameLogEntityManager, $type, Survey $survey): Response
    {
        $blameLogRepository = $blameLogEntityManager->getRepository(BlameLog::class);
        return $this->render('admin/domestic/surveys/log.html.twig', [
            'survey' => $survey,
            'log' => $blameLogRepository->getAllLogsForEntity($survey),
        ]);
    }

}
