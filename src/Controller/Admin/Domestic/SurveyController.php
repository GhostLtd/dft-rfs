<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\BlameLog\BlameLog;
use App\Entity\Domestic\Survey;
use App\ListPage\Domestic\SurveyListPage;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/surveys")
 */
class SurveyController extends AbstractController
{
    private const ROUTE_PREFIX = 'admin_domestic_survey_';
    public const DELETE_ROUTE = self::ROUTE_PREFIX.'delete';
    public const LIST_ROUTE = self::ROUTE_PREFIX.'list';
    public const LOGS_ROUTE = self::ROUTE_PREFIX.'logs';
    public const VIEW_ROUTE = self::ROUTE_PREFIX.'view';

    public const EDIT_INITIAL_ROUTE = self::ROUTE_PREFIX.'initial_edit';
    public const EDIT_BUSINESS_ROUTE = self::ROUTE_PREFIX.'business_edit';

    /**
     * @Route("/{type}", requirements={"type": "gb|ni"}, name=self::LIST_ROUTE)
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
     * @Route("/{surveyId}", name=self::VIEW_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function viewDetails(Survey $survey): Response
    {
        return $this->render('admin/domestic/surveys/view.html.twig', [
            'survey' => $survey,
        ]);
    }

    /**
     * @Route("/{surveyId}/audit-log", name=self::LOGS_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
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
