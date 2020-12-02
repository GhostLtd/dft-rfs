<?php

namespace App\Controller\InternationalSurvey;

use App\Repository\International\SurveyRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class VehicleController extends AbstractController
{
    use SurveyHelperTrait;

    public const SUMMARY_ROUTE = 'app_internationalsurvey_vehicle_summary';

    protected $surveyRepo;

    public function __construct(SurveyRepository $surveyRepo)
    {
        $this->surveyRepo = $surveyRepo;
    }

    /**
     * @Route("/international-survey/vehicles/{registrationMark}", name=self::SUMMARY_ROUTE)
     */
    public function index(UserInterface $user, string $registrationMark) {
        $survey = $this->getSurvey($user);
        $response = $survey->getResponse();

//        if ($survey->getSubmissionDate()) {
//            return $this->render('international_survey/thanks.html.twig');
//        }
//
//        if (!$survey->isInitialDetailsComplete()) {
//            return $this->redirectToRoute(InitialDetailsController::WIZARD_ROUTE, ['state' => InitialDetailsState::STATE_INTRODUCTION]);
//        }
//
//        if (!$response->isInitialDetailsSignedOff()) {
//            return $this->redirectToRoute(BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE);
//        }

        return $this->render('international_survey/vehicle/summary.html.twig', [
            'survey' => $survey,
            'registrationMark' => $registrationMark,
        ]);
    }
}