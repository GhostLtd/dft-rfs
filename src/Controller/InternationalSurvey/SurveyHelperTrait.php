<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Survey;
use App\Entity\International\SurveyResponse;
use App\Entity\PasscodeUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait SurveyHelperTrait
{
    protected function getSurvey(): Survey
    {
        $user = $this->getUser();
        $survey = ($user instanceof PasscodeUser) ? $user->getInternationalSurvey() : null;

        if (!$survey instanceof Survey) {
            throw new AccessDeniedHttpException('No such survey');
        }

        return $survey;
    }

    protected function getSurveyResponse(): SurveyResponse
    {
        $response = $this->getSurvey()->getResponse();

        if (!$response instanceof SurveyResponse) {
            throw new AccessDeniedHttpException('No such survey response');
        }

        return $response;
    }
}