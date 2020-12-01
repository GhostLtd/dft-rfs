<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Survey;
use App\Entity\International\Vehicle;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\VehicleState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class VehicleController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_vehicle_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_vehicle_state';

    /**
     * @Route("/international-survey/add-vehicle/{state}", name=self::WIZARD_ROUTE)
     * @Route("/international-survey/add-vehicle", name=self::START_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyVehicleStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($internationalSurveyVehicleStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        $survey = $this->getSurvey($this->getUser());

        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new VehicleState());

        $response = $formWizard->getSubject() ?? $this->getVehicle($survey);
        $this->entityManager->persist($response);
        $formWizard->setSubject($response);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }

    protected function getVehicle(Survey $survey): Vehicle
    {
        $response = $survey->getResponse();

        if (!$response) {
            throw new UnauthorizedHttpException('No such survey response');
        }

        return (new Vehicle())->setSurveyResponse($response);
    }
}
