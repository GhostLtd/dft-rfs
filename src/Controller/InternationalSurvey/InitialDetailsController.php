<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\SurveyResponse;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class InitialDetailsController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_initial_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_initial_state';

    protected SurveyResponse $surveyResponse;

    #[Route(path: '/international-survey/initial-details/{state}', name: self::WIZARD_ROUTE)]
    #[Route(path: '/international-survey/initial-details', name: self::START_ROUTE)]
    public function index(WorkflowInterface $internationalSurveyInitialDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($internationalSurveyInitialDetailsStateMachine, $request, $state);
    }

    #[\Override]
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

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return $this->surveyResponse->getId() ? $this->redirectToRoute(BusinessAndCorrespondenceDetailsController::SUMMARY_ROUTE) : null;
    }
}
