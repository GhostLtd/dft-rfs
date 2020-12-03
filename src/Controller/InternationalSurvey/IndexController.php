<?php

namespace App\Controller\InternationalSurvey;

use App\Repository\International\SurveyRepository;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class IndexController extends AbstractController
{
    use SurveyHelperTrait;

    public const SUMMARY_ROUTE = 'app_internationalsurvey_summary';

    protected $surveyRepo;

    public function __construct(SurveyRepository $surveyRepo)
    {
        $this->surveyRepo = $surveyRepo;
    }

    /**
     * @Route("/international-survey", name=self::SUMMARY_ROUTE)
     */
    public function index(UserInterface $user) {
        $survey = $this->getSurvey($user);

        if ($survey->getSubmissionDate()) {
            return $this->render('international_survey/thanks.html.twig');
        }

        if (!$survey->isInitialDetailsComplete()) {
            return $this->redirectToRoute(InitialDetailsController::WIZARD_ROUTE, ['state' => InitialDetailsState::STATE_INTRODUCTION]);
        }

        $response = $survey->getResponse();

        if (!$response->isInitialDetailsSignedOff()) {
            return $this->redirectToRoute(BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE);
        }

        $vehicles = $response->getVehicles();

        return $this->render('international_survey/summary.html.twig', [
            'response' => $response,
            'vehicles' => $vehicles,
        ]);
    }
}