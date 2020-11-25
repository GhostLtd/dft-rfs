<?php


namespace App\Controller\InternationalSurvey;


use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;

abstract class BaseSessionStateWorkflowController extends AbstractSessionStateWorkflowController
{
    protected function getSurveyResponse(Survey $survey, ?SurveyResponse $existingResponse): SurveyResponse
    {
        $response = $existingResponse ?? $survey->getResponse();

        if (!$response) {
            $response = new SurveyResponse();
        }

        if ($response->getSurvey() === null) {
            $response->setSurvey($survey);
        }

        return $response;
    }
}