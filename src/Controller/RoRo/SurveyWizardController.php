<?php

namespace App\Controller\RoRo;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\RoRo\Survey;
use App\Security\Voter\RoRo\SurveyVoter;
use App\Utility\RoRo\VehicleCountHelper;
use App\Workflow\FormWizardManager;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\RoRo\RoRoState;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route("/roro/survey/{surveyId}/edit/{state}", name: "app_roro_survey_")]
#[Entity("survey", expr: "repository.find(surveyId)")]
#[IsGranted(SurveyVoter::EDIT, "survey", statusCode: 403)]
class SurveyWizardController extends AbstractSessionStateWorkflowController
{
    protected Survey $databaseSurvey;

    #[Route(name: "edit")]
    public function wizard(WorkflowInterface $roroStateMachine, Request $request, Survey $survey, VehicleCountHelper $vehicleCountHelper, ?string $state = null): array | Response
    {
        $this->databaseSurvey = $survey;
        $vehicleCountHelper->setVehicleCountLabels($survey->getVehicleCounts());

        return $this->doWorkflow($roroStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var RoRoState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), (new RoRoState())->setSubject($this->databaseSurvey));
        $formData = $formWizard->getSubject();

        $this->databaseSurvey->merge($formData);
        $formWizard->setSubject($this->databaseSurvey);
        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute('app_roro_survey_edit', ['surveyId' => $this->databaseSurvey->getId(), 'state' => $state]);
    }

    protected function getCancelUrl(): ?Response
    {
        return $this->redirectToRoute('app_roro_survey_view', ['surveyId' => $this->databaseSurvey->getId()]);
    }
}