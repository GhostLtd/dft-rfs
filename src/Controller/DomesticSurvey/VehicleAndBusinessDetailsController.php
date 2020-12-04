<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\PasscodeUser;
use App\Workflow\DomesticSurvey\VehicleAndBusinessDetailsState;
use App\Workflow\FormWizardInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class VehicleAndBusinessDetailsController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_vehicleandbusinessdetails_index';

    /**
     * @Route("/domestic-survey/vehicle-and-business-details", name="app_domesticsurvey_vehicleandbusinessdetails_start")
     * @Route("/domestic-survey/vehicle-and-business-details/{state}", name=self::ROUTE_NAME)
     * @param WorkflowInterface $domesticSurveyVehicleAndBusinessDetailsStateMachine
     * @param Request $request
     * @param null | string $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $domesticSurveyVehicleAndBusinessDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($domesticSurveyVehicleAndBusinessDetailsStateMachine, $request, $state);
    }

    /**
     * @return FormWizardInterface
     */
    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();

        $survey = $user->getDomesticSurvey();
        $response = $this->entityManager->getRepository(SurveyResponse::class)->getBySurvey($survey);

        /** @var VehicleAndBusinessDetailsState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new VehicleAndBusinessDetailsState());
        if (is_null($formWizard->getSubject())) {
            $formWizard->setSubject($response);
        }
        // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
        $formWizard->getSubject()->setVehicle($this->entityManager->merge($formWizard->getSubject()->getVehicle()));
        $formWizard->setSubject($this->entityManager->merge($formWizard->getSubject()));

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['state' => $state]);
    }
}
