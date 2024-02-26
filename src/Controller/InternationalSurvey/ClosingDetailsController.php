<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Survey;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\ClosingDetailsState;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class ClosingDetailsController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_closing_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_closing_state';

    protected Survey $survey;

    #[Route(path: '/international-survey/closing-details/{state}', name: self::WIZARD_ROUTE)]
    #[Route(path: '/international-survey/closing-details', name: self::START_ROUTE)]
    public function index(WorkflowInterface $internationalSurveyClosingDetailsStateMachine, Request $request, string $state = null): Response
    {
        $this->survey = $this->getSurvey();

        if (!$state || $state === ClosingDetailsState::STATE_START) {
            $formWizard = $this->getFormWizard();

            if ($this->session->has($this->getSessionKey())) {
                $referer = parse_url($request->headers->get('referer', ''));
                $summaryUrl = $this->redirectToRoute(IndexController::SUMMARY_ROUTE);
                if (($referer['path'] ?? false) === $summaryUrl->getTargetUrl()) {
                    // if the referer is summary screen, start the wizard
                    $formWizard->setState(ClosingDetailsState::STATE_START);
                } else {
                    // otherwise the referer must be within the wizard already, go back to summary
                    return $summaryUrl;
                }
            }

            $transitions = array_values($internationalSurveyClosingDetailsStateMachine->getEnabledTransitions($formWizard));
            return $this->applyTransitionAndRedirect($request, $internationalSurveyClosingDetailsStateMachine, $formWizard, $transitions[0]);
        }

        return $this->doWorkflow($internationalSurveyClosingDetailsStateMachine, $request, $state);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var FormWizardStateInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ClosingDetailsState());

        $surveyResponse = $formWizard->getSubject() ?? $this->survey;
        $this->survey->mergeClosingDetails($surveyResponse);
        $formWizard->setSubject($this->survey);

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
        return null;
    }
}
