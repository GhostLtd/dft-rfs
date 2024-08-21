<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\VehicleState;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class VehicleAddController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_vehicle_add_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_vehicle_add_state';

    protected SurveyResponse $surveyResponse;

    #[Route(path: '/international-survey/add-vehicle/{state}', name: self::WIZARD_ROUTE)]
    #[Route(path: '/international-survey/add-vehicle', name: self::START_ROUTE)]
    public function index(WorkflowInterface $internationalSurveyVehicleStateMachine,
                          Request $request,
                          $state = null): Response
    {
        $this->surveyResponse = $this->getSurveyResponse();
        return $this->doWorkflow($internationalSurveyVehicleStateMachine, $request, $state);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new VehicleState());

        $this->setSubjectOnWizard($this->surveyResponse, $formWizard);

        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
    }

    protected function setSubjectOnWizard(SurveyResponse $response, FormWizardStateInterface $formWizard): void
    {
        $vehicle = $formWizard->getSubject() ?? new Vehicle();
        $vehicle->setSurveyResponse($response);
        $formWizard->setSubject($vehicle);
    }
}
