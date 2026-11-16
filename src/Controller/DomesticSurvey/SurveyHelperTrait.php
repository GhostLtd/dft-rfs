<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\PasscodeUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait SurveyHelperTrait
{
    protected function getSurvey(): Survey
    {
        $user = $this->getUser();
        $survey = ($user instanceof PasscodeUser) ? $user->getDomesticSurvey() : null;

        if (!$survey) {
            throw new AccessDeniedHttpException('No such survey');
        }

        return $survey;
    }

    protected function getSurveyResponse(): SurveyResponse
    {
        $response = $this->getSurvey()->getResponse();

        if (!$response) {
            throw new AccessDeniedHttpException('No such survey response');
        }

        return $response;
    }
}