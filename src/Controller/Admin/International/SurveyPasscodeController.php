<?php

namespace App\Controller\Admin\International;

use App\Entity\International\Survey;
use App\Security\Voter\AdminSurveyVoter;
use App\Utility\PasscodeGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SurveyPasscodeController extends AbstractController
{
    /**
     * @Route("/irhs/surveys/{surveyId}/reveal-passcode", name="admin_international_survey_reveal_passcode")
     * @Template("admin/international/reveal_passcode/index.html.twig")
     * @Entity("survey", expr="repository.find(surveyId)")
     * @IsGranted(AdminSurveyVoter::RESET_PASSCODE, subject="survey")
     */
    public function resetPasscodeSuccess(PasscodeGenerator $passcodeGenerator, Survey $survey): array
    {
        return [
            'survey' => $survey,
            'code' => $passcodeGenerator->getPasswordForUser($survey->getPasscodeUser()),
        ];
    }
}