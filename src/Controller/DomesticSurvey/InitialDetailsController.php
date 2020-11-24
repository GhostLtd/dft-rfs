<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Entity\PasscodeUser;
use App\Workflow\DomesticSurvey\InitialDetailsState;
use App\Workflow\FormWizardInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class InitialDetailsController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_initialdetails_index';

    /**
     * @Route("/domestic-survey/initial-details/{state}", name=self::ROUTE_NAME)
     * @Route("/domestic-survey/initial-details", name="app_domesticsurvey_initialdetails_start")
     * @param WorkflowInterface $domesticSurveyInitialDetailsStateMachine
     * @param Request $request
     * @param null $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $domesticSurveyInitialDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($domesticSurveyInitialDetailsStateMachine, $request, $state);
    }

    /**
     * @return FormWizardInterface
     */
    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $survey = $user->getDomesticSurvey();

        /** @var InitialDetailsState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new InitialDetailsState());
        if (is_null($formWizard->getSubject())) {
            $vehicle = (new Vehicle())
                ->setRegistrationMark($survey->getRegistrationMark());
            $surveyResponse = $survey->getResponse() ?? (new SurveyResponse())->setVehicle($vehicle);
            $surveyResponse->setSurvey($survey);
            $formWizard->setSubject($surveyResponse);
        } else {
            $surveyResponse = $formWizard->getSubject();
            $surveyResponse->setSurvey($survey);
        }

        if ($formWizard->getSubject()->getId()) {
            // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            $formWizard->setSubject($this->getDoctrine()->getManager()->merge($formWizard->getSubject()));
        } else {
            $this->entityManager->persist($formWizard->getSubject());
        }

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['state' => $state]);
    }
}
