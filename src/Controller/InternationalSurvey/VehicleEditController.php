<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Vehicle;
use App\Repository\International\VehicleRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\VehicleState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class VehicleEditController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const WIZARD_ROUTE = 'app_internationalsurvey_vehicle_edit_state';

    /**
     * @var Vehicle
     */
    protected $vehicle;

    /**
     * @Route("/international-survey/vehicle/{registrationMark}/{state}", name=self::WIZARD_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyVehicleStateMachine, Request $request, VehicleRepository $vehicleRepository, string $registrationMark, string $state): Response
    {
        $surveyResponse = $this->getSurveyResponse($this->getUser());
        $this->vehicle = $vehicleRepository->findOneBy(['registrationMark' => $registrationMark, 'surveyResponse' => $surveyResponse]);

        if (!$this->vehicle) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyVehicleStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new VehicleState());

        $vehicle = $formWizard->getSubject() ?? $this->vehicle;
        $this->vehicle->mergeVehicleChanges($vehicle);
        $formWizard->setSubject($this->vehicle);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['registrationMark' => $this->vehicle->getRegistrationMark(), 'state' => $state]);
    }
}
