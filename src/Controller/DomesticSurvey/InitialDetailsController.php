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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class InitialDetailsController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_initialdetails_index';

    /**
     * @Route("/domestic-survey/initial-details", name="app_domesticsurvey_initialdetails_start")
     * @Route("/domestic-survey/initial-details/{state}", name=self::ROUTE_NAME)
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
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
        $em = $this->getDoctrine()->getManager();

        /** @var PasscodeUser $user */
        $user = $this->getUser();
        $survey = $user->getDomesticSurvey();
        $survey = $em->getRepository(Survey::class)->findOneByIdWithResponseAndVehicle($survey->getId());

        /** @var InitialDetailsState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new InitialDetailsState());
        if (is_null($formWizard->getSubject())) {
            $vehicle = (new Vehicle())
                ->setRegistrationMark($survey->getRegistrationMark())
                ->setResponse($surveyResponse = $survey->getResponse() ?? (new SurveyResponse()))
            ;
            $surveyResponse->setSurvey($survey);
            $formWizard->setSubject($surveyResponse);
        }

        if ($formWizard->getSubject()->getId()) {
            $surveyResponse = $survey->getResponse();
            $surveyResponse
                ->mergeInitialDetails($formWizard->getSubject())
              ;
            $formWizard->setSubject($surveyResponse);
        } else {
            $survey->setResponse($formWizard->getSubject());
        }

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['state' => $state]);
    }
}
