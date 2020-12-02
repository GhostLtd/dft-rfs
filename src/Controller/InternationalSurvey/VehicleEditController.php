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

class VehicleEditController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const WIZARD_ROUTE = 'app_internationalsurvey_vehicle_edit_state';

    /**
     * @Route("/international-survey/vehicle/{registrationMark}/{state}", name=self::WIZARD_ROUTE)
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

        $this->setSubjectOnWizard($survey, $formWizard);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }

    protected function setSubjectOnWizard(Survey $survey, FormWizardInterface $formWizard): void
    {
        $response = $survey->getResponse();
        if (!$response) {
            throw new UnauthorizedHttpException('No such survey response');
        }

        // TODO
        $vehicle = $formWizard->getSubject() ?? new Vehicle();
        $vehicle->setSurveyResponse($survey->getResponse());
        $this->entityManager->persist($vehicle);
        $formWizard->setSubject($vehicle);
    }
}
