<?php

namespace App\Controller;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\DomesticSurvey;
use App\Workflow\DomesticSurveyVehicleAndBusinessDetailsState;
use App\Workflow\FormWizardInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class DomesticSurveyVehicleAndBusinessDetailsController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurveyvehicleandbusinessdetails_index';

    /**
     * @Route("/domestic-survey/vehicle-and-business-details/{state}", name=DomesticSurveyVehicleAndBusinessDetailsController::ROUTE_NAME)
     * @Route("/domestic-survey/vehicle-and-business-details", name="app_domesticsurveyvehicleandbusinessdetails_start")
     * @param WorkflowInterface $domesticSurveyVehicleAndBusinessDetailsStateMachine
     * @param Request $request
     * @param null $state
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
        /** @var DomesticSurveyVehicleAndBusinessDetailsState $formWizard */
        $formWizard = $this->session->get(self::SESSION_KEY, new DomesticSurveyVehicleAndBusinessDetailsState());
        if (is_null($formWizard->getSubject())) {
            $survey = $this->entityManager->getRepository(DomesticSurvey::class)->findLatestSurveyForTesting();
            $formWizard->setSubject($survey->getSurveyResponse());
        }
        // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
        $formWizard->getSubject()->setVehicle($this->entityManager->merge($formWizard->getSubject()->getVehicle()));
        $formWizard->setSubject($this->entityManager->merge($formWizard->getSubject()));
        return $formWizard;
    }

    protected function getRouteName(): string
    {
        return self::ROUTE_NAME;
    }
}
