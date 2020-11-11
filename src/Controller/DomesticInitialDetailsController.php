<?php

namespace App\Controller;

use App\Entity\DomesticSurveyResponse;
use App\Workflow\DomesticSurveyState;
use App\Workflow\FormWizardInterface;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class DomesticInitialDetailsController extends AbstractWorkflowController
{
    public const ROUTE_NAME = 'app_domesticinitialdetails_index';

    /**
     * @Route("/domestic-sruvey/initial-details/{state}", name=DomesticInitialDetailsController::ROUTE_NAME)
     * @Route("/domestic-sruvey/initial-details", name="app_domesticinitialdetails_start")
     * @param WorkflowInterface $domesticSurveyInitialDetailsStateMachine
     * @param Request $request
     * @param null $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $domesticSurveyInitialDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($domesticSurveyInitialDetailsStateMachine, $request, $state);
    }



    /**
     * @param Request $request
     * @return FormWizardInterface
     */
    protected function getFormWizard(Request $request): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $request->getSession()->get(self::SESSION_KEY, new DomesticSurveyState());
        if (is_null($formWizard->getSubject())) {
            $surveyResponses = $this->getDoctrine()->getRepository(DomesticSurveyResponse::class)->findAll();
            $formWizard->setSubject(array_pop($surveyResponses));
        }

        // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
        $formWizard->setSubject($this->getDoctrine()->getManager()->merge($formWizard->getSubject()));
        return $formWizard;
    }

    protected function getRouteName(): string
    {
        return self::ROUTE_NAME;
    }

    protected function getDefaultTemplate(): string
    {
        return 'domestic_survey/initial_details/form-step.html.twig';
    }
}
