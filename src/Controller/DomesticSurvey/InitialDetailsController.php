<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\Domestic\Vehicle;
use App\Workflow\DomesticSurvey\InitialDetailsState;
use App\Workflow\FormWizardStateInterface;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class InitialDetailsController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_initialdetails_index';

    protected SurveyResponse $surveyResponse;

    use SurveyHelperTrait;

    #[IsGranted(new Expression("is_granted('EDIT', user.getDomesticSurvey())"))]
    #[Route(path: '/domestic-survey/initial-details', name: 'app_domesticsurvey_initialdetails_start')]
    #[Route(path: '/domestic-survey/initial-details/{state}', name: self::ROUTE_NAME)]
    public function index(WorkflowInterface $domesticSurveyInitialDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($domesticSurveyInitialDetailsStateMachine, $request, $state);
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        $survey = $this->getSurvey();
        $databaseSurvey = $this->entityManager->getRepository(Survey::class)
            ->findOneByIdWithResponseAndVehicle($survey->getId());

        /** @var InitialDetailsState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), (new InitialDetailsState())->setSubject($databaseSurvey->getResponse()));

        // it's the start of the wizard, and it's a fresh survey (not editing)
        if (is_null($formWizard->getSubject())) {
            (new Vehicle())
                ->setRegistrationMark($databaseSurvey->getRegistrationMark())
                ->setResponse($surveyResponse = $databaseSurvey->getResponse() ?? (new SurveyResponse()));

            $surveyResponse->setSurvey($databaseSurvey);
            $formWizard->setSubject($surveyResponse);
        }

        if ($formWizard->getSubject()->getId()) {
            $surveyResponse = $databaseSurvey->getResponse();
            $surveyResponse
                ->mergeInitialDetails($formWizard->getSubject())
              ;
            $formWizard->setSubject($surveyResponse);
        } else {
            $databaseSurvey->setResponse($formWizard->getSubject());
        }

        $this->surveyResponse = $formWizard->getSubject();
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
        return $this->surveyResponse->getId() ? $this->redirectToRoute('app_domesticsurvey_contactdetails') : null;
    }
}
