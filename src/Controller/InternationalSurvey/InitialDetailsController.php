<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\SurveyResponse;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Security("is_granted('EDIT', user.getInternationalSurvey())")
 */
class InitialDetailsController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_initial_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_initial_state';

    protected SurveyResponse $surveyResponse;

    /**
     * @Route("/international-survey/initial-details/{state}", name=self::WIZARD_ROUTE)
     * @Route("/international-survey/initial-details", name=self::START_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyInitialDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($internationalSurveyInitialDetailsStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardStateInterface
    {
        $survey = $this->getSurvey();

        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new InitialDetailsState());

        $response = $formWizard->getSubject() ?? $survey->getResponse() ?? new SurveyResponse();

        $databaseResponse = $survey->getResponse() ?? new SurveyResponse();
        $databaseResponse->mergeInitialDetails($response);
        $survey->setResponse($databaseResponse);
        $formWizard->setSubject($databaseResponse);

        $this->surveyResponse = $formWizard->getSubject();
        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }

    protected function getCancelUrl(): ?Response
    {
        return $this->surveyResponse->getId() ? $this->redirectToRoute(BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE) : null;
    }
}
