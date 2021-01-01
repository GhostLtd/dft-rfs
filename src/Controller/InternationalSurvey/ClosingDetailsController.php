<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\ClosingDetailsState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_closing_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_closing_state';

    protected $surveyResponse;

    /**
     * @Route("/international-survey/closing-details/{state}", name=self::WIZARD_ROUTE)
     * @Route("/international-survey/closing-details", name=self::START_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyClosingDetailsStateMachine, Request $request, UserInterface $user, string $state = null): Response
    {
        $this->surveyResponse = $this->getSurveyResponse($user);

        if (!$state || $state === ClosingDetailsState::STATE_START) {
            if ($this->session->has($this->getSessionKey())) {
                return $this->redirectToRoute('app_internationalsurvey_summary');
            }

            $formWizard = $this->getFormWizard();
            $transitions = array_values($internationalSurveyClosingDetailsStateMachine->getEnabledTransitions($formWizard));
            return $this->applyTransitionAndRedirect($request, $internationalSurveyClosingDetailsStateMachine, $formWizard, $transitions[0]);
        }

        return $this->doWorkflow($internationalSurveyClosingDetailsStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ClosingDetailsState());

        $surveyResponse = $formWizard->getSubject() ?? $this->surveyResponse;
        $this->surveyResponse->mergeClosingDetails($surveyResponse);
        $formWizard->setSubject($this->surveyResponse);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }
}