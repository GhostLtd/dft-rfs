<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\SurveyResponse;
use App\Workflow\DomesticSurvey\VehicleAndBusinessDetailsState;
use App\Workflow\FormWizardStateInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class VehicleAndBusinessDetailsController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_vehicleandbusinessdetails_index';

    use SurveyHelperTrait;

    #[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
    #[Route(path: '/domestic-survey/vehicle-and-business-details', name: 'app_domesticsurvey_vehicleandbusinessdetails_start')]
    #[Route(path: '/domestic-survey/vehicle-and-business-details/{state}', name: self::ROUTE_NAME)]
    public function index(WorkflowInterface $domesticSurveyVehicleAndBusinessDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($domesticSurveyVehicleAndBusinessDetailsStateMachine, $request, $state);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        $survey = $this->getSurvey();
        $databaseResponse = $this->entityManager->getRepository(SurveyResponse::class)->getBySurvey($survey);

        /** @var VehicleAndBusinessDetailsState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new VehicleAndBusinessDetailsState());

        $sessionResponse = $formWizard->getSubject();

        if ($sessionResponse) {
            $databaseResponse->mergeVehicleAndBusinessDetails($sessionResponse);
        }

        $formWizard->setSubject($databaseResponse);
        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute('app_domesticsurvey_contactdetails');
    }
}
