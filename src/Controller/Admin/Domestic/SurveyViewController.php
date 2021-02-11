<?php


namespace App\Controller\Admin\Domestic;


use App\Entity\Domestic\Survey;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SurveyViewController extends AbstractController
{
    /**
     * @Route("/csrgt/surveys/{surveyId}", name=SurveyController::VIEW_ROUTE)
     * @Entity("survey", expr="repository.find(surveyId)")
     */
    public function viewDetails(Survey $survey): Response
    {
        return $this->render('admin/domestic/surveys/view.html.twig', [
            'survey' => $survey,
        ]);
    }
}