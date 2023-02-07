<?php

namespace App\Controller\DomesticSurvey;

use App\Annotation\Redirect;
use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\SurveyResponse;
use App\Workflow\DomesticSurvey\ClosingDetailsState;
use App\Workflow\FormWizardStateInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_closingdetails_index';
    public const START_ROUTE_NAME = 'app_domesticsurvey_closingdetails_start';

    use SurveyHelperTrait;

    /**
     * @Route("/domestic-survey/closing-details", name=self::START_ROUTE_NAME)
     * @Route("/domestic-survey/closing-details/{state}", name=self::ROUTE_NAME)
     * @Redirect("is_granted('VIEW_SUBMISSION_SUMMARY', user.getDomesticSurvey())", route="app_domesticsurvey_completed")
     * @Security("is_granted('CLOSING_DETAILS', user.getDomesticSurvey())")
     */
    public function index(WorkflowInterface $domesticSurveyClosingDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($domesticSurveyClosingDetailsStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardStateInterface
    {
        $survey = $this->getSurvey();
        $databaseResponse = $this->entityManager->getRepository(SurveyResponse::class)
            ->getBySurvey($survey);

        /** @var ClosingDetailsState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ClosingDetailsState());

        $sessionResponse = $formWizard->getSubject();
        if ($sessionResponse) {
            $driverAvailability = $sessionResponse->getSurvey()->getDriverAvailability();
            $driverAvailability = $databaseResponse->getSurvey()->mergeDriverAvailability($driverAvailability);

            $vehicle = $sessionResponse->getVehicle();
            $databaseResponse->mergeVehicleFuel($vehicle);

            $databaseResponse->mergeReasonForEmptySurvey($sessionResponse);

            if (!$driverAvailability->getId()) {
                $this->entityManager->persist($driverAvailability);
            }
        }

        $formWizard->setSubject($databaseResponse);
        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['state' => $state]);
    }

    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
    }
}