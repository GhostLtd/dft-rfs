<?php

namespace App\Controller\InternationalSurvey;

use App\Entity\International\Survey;
use App\Workflow\InternationalSurvey\ClosingDetailsState;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[IsGranted(new Expression("is_granted('EDIT', user.getInternationalSurvey())"))]
class ClosingDetailsController extends AbstractSessionStateFormWizardController
{
    use SurveyHelperTrait;

    protected Survey $survey;

    public function __construct(
        FormWizardManager         $formWizardManager,
        RequestStack              $requestStack,
        PropertyMerger            $propertyMerger,
        protected WorkflowInterface $internationalSurveyClosingDetailsStateMachine,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    #[Route(path: '/international-survey/closing-details', name: 'app_internationalsurvey_closing_start')]
    #[Route(path: '/international-survey/closing-details/{state}', name: 'app_internationalsurvey_closing_wizard')]
    public function index(Request $request, ?string $state = null): Response
    {
        $this->survey = $this->getSurvey();

        if (!$state || $state === ClosingDetailsState::STATE_START) {
            // STATE_START is a routing-only place: pick which of the START-leaving
            // transitions has its guard satisfied right now and fire that.
            $formWizardState = $this->getState();

            if ($this->session->has($this->getSessionKey())) {
                $referer = parse_url($request->headers->get('referer', ''));
                $summaryUrl = $this->generateUrl(IndexController::SUMMARY_ROUTE);
                if (($referer['path'] ?? false) === $summaryUrl) {
                    // referer was summary screen → restart the wizard from STATE_START
                    $formWizardState->setPlace(ClosingDetailsState::STATE_START);
                } else {
                    // already inside the wizard → bail back to summary
                    return new RedirectResponse($summaryUrl);
                }
            }

            $transitions = array_values($this->internationalSurveyClosingDetailsStateMachine->getEnabledTransitions($formWizardState));
            return $this->applyTransitionAndRedirect($formWizardState, $transitions[0], $request);
        }

        return $this->doWorkflow($request, $state);
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var ClosingDetailsState $state */
        $state = $this->session->get($this->getSessionKey(), new ClosingDetailsState());

        $sessionSurvey = $state->getSubject() ?? $this->survey;
        $this->survey->mergeClosingDetails($sessionSurvey);
        $state->setSubject($this->survey);

        // is_correct is a one-shot, per-form flag. Reset it on each request so a
        // prior step's value doesn't leak in as a preselected radio (which would
        // suppress the field's NotNull error if the user clears it but submits).
        $state->setIsCorrect(null);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return new RedirectResponse($this->generateUrl('app_internationalsurvey_closing_wizard', ['state' => (string) $place]));
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return null;
    }
}
