<?php

namespace App\Controller\RoRo;

use App\Entity\RoRo\Survey;
use App\Utility\RoRo\VehicleCountHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SurveyViewController extends AbstractController
{
    #[Route("/roro/survey/{surveyId}/view", name: "app_roro_survey_view")]
    #[Entity("survey", expr: "repository.find(surveyId)")]
    #[Security("is_granted('CAN_VIEW_RORO_SURVEY', survey)")]
    #[Template('roro/survey/view.html.twig')]
    public function __invoke(Survey $survey, VehicleCountHelper $vehicleCountHelper): array
    {
        $vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());

        return [
            'operator' => $survey->getOperator(),
            'survey' => $survey,
        ];
    }
}