<?php

namespace App\Controller\RoRo;

use App\Entity\RoRo\Survey;
use App\Utility\RoRo\VehicleCountHelper;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;

class SurveyViewController extends AbstractController
{
    #[Route("/roro/survey/{surveyId}/view", name: "app_roro_survey_view")]
    #[IsGranted("CAN_VIEW_RORO_SURVEY", "survey")]
    #[Template('roro/survey/view.html.twig')]
    public function __invoke(
        #[MapEntity(expr: "repository.find(surveyId)")]
        Survey $survey,
        VehicleCountHelper $vehicleCountHelper
    ): array
    {
        $vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());

        return [
            'operator' => $survey->getOperator(),
            'survey' => $survey,
        ];
    }
}
