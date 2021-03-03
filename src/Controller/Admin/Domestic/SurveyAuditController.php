<?php

namespace App\Controller\Admin\Domestic;

use App\Entity\BlameLog\BlameLog;
use App\Entity\Domestic\Survey;
use App\Security\Voter\Domestic\AdminSurveyVoter;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/csrgt/surveys/{surveyId}")
 * @Entity("survey", expr="repository.find(surveyId)")
 * @IsGranted(AdminSurveyVoter::EDIT, subject="survey")
 */
class SurveyAuditController extends AbstractController
{
    /**
     * @Route("/audit-log", name=SurveyController::LOGS_ROUTE)
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