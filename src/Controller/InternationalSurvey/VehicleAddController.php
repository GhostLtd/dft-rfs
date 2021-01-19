<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Vehicle;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\VehicleState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Security("is_granted('EDIT', user.getInternationalSurvey())")
 */
class VehicleAddController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_vehicle_add_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_vehicle_add_state';

    protected $surveyResponse;

    /**
     * @Route("/international-survey/add-vehicle/{state}", name=self::WIZARD_ROUTE)
     * @Route("/international-survey/add-vehicle", name=self::START_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyVehicleStateMachine,
                          Request $request,
                          UserInterface $user,
                          $state = null): Response
    {
        $this->surveyResponse = $this->getSurveyResponse($user);
        return $this->doWorkflow($internationalSurveyVehicleStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new VehicleState());

        $this->setSubjectOnWizard($this->surveyResponse, $formWizard);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }

    protected function setSubjectOnWizard(SurveyResponse $response, FormWizardStateInterface $formWizard): void
    {
        $vehicle = $formWizard->getSubject() ?? new Vehicle();
        $vehicle->setSurveyResponse($response);
        $formWizard->setSubject($vehicle);
    }
}
