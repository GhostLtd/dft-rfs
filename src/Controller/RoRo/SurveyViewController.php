<?php

namespace App\Controller\RoRo;

use App\Entity\RoRo\Survey;
use App\Utility\RoRo\VehicleCountHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/roro/survey/{surveyId}/view", name: "app_roro_survey_")]
#[Entity("survey", expr: "repository.find(surveyId)")]
class SurveyViewController extends AbstractController
{
    #[Route(name: "view")]
    #[Template('roro/survey/view.html.twig')]
    public function wizard(Survey $survey, VehicleCountHelper $vehicleCountHelper): array
    {
        $vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());

        return [
            'survey' => $survey,
        ];
    }
}