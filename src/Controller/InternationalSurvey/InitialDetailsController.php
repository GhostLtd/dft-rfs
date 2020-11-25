<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\SurveyResponse;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\InitialDetailsState;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class InitialDetailsController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_initial_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_initial_state';

    /**
     * @Route("/international-survey/initial-details/{state}", name=self::WIZARD_ROUTE)
     * @Route("/international-survey/initial-details", name=self::START_ROUTE)
     * @param WorkflowInterface $internationalSurveyInitialDetailsStateMachine
     * @param Request $request
     * @param null $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $internationalSurveyInitialDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($internationalSurveyInitialDetailsStateMachine, $request, $state);
    }

    /**
     * @return FormWizardInterface
     */
    protected function getFormWizard(): FormWizardInterface
    {
        $survey = $this->getSurvey($this->getUser());

        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new InitialDetailsState());

        $response = $formWizard->getSubject() ?? $survey->getResponse() ?? new SurveyResponse();
        $survey->setResponse($response);

        if ($this->entityManager->contains($response)) {
            // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
            $response = $this->entityManager->merge($response);
        } else {
            $this->entityManager->persist($response);
        }

        $formWizard->setSubject($response);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['state' => $state]);
    }
}
