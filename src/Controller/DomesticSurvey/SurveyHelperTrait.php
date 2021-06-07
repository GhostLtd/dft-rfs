<?php

namespace App\Controller\DomesticSurvey;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\PasscodeUser;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\User\UserInterface;

trait SurveyHelperTrait
{
    protected function getSurvey(UserInterface $user): Survey
    {
        $survey = ($user instanceof PasscodeUser) ? $user->getDomesticSurvey() : null;

        if (!$survey || !$survey instanceof Survey) {
            throw new AccessDeniedHttpException('No such survey');
        }

        return $survey;
    }

    protected function getSurveyResponse(UserInterface $user): SurveyResponse
    {
        $response = $this->getSurvey($user)->getResponse();

        if (!$response || !$response instanceof SurveyResponse) {
            throw new AccessDeniedHttpException('No such survey response');
        }

        return $response;
    }
}