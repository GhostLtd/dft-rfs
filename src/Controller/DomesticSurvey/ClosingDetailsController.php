<?php

namespace App\Controller\DomesticSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\Domestic\SurveyResponse;
use App\Entity\PasscodeUser;
use App\Workflow\DomesticSurvey\ClosingDetailsState;
use App\Workflow\FormWizardInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class ClosingDetailsController extends AbstractSessionStateWorkflowController
{
    public const ROUTE_NAME = 'app_domesticsurvey_closingdetails_index';
    public const START_ROUTE_NAME = 'app_domesticsurvey_closingdetails_start';

    /**
     * @var WorkflowInterface
     */
    private $domesticSurveyStateMachine;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $log, SessionInterface $session, WorkflowInterface $domesticSurveyStateMachine)
    {
        parent::__construct($entityManager, $log, $session);
        $this->domesticSurveyStateMachine = $domesticSurveyStateMachine;
    }

    /**
     * @Route("/domestic-survey/closing-details", name=self::START_ROUTE_NAME)
     * @Route("/domestic-survey/closing-details/{state}", name=self::ROUTE_NAME)
     * @Security("is_granted('EDIT', user.getDomesticSurvey())")
     * @param WorkflowInterface $domesticSurveyClosingDetailsStateMachine
     * @param Request $request
     * @param null | string $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $domesticSurveyClosingDetailsStateMachine, Request $request, $state = null): Response
    {
        return $this->doWorkflow($domesticSurveyClosingDetailsStateMachine, $request, $state);
    }

    /**
     * @return FormWizardInterface
     */
    protected function getFormWizard(): FormWizardInterface
    {
        /** @var PasscodeUser $user */
        $user = $this->getUser();

        $survey = $user->getDomesticSurvey();
        $response = $this->entityManager->getRepository(SurveyResponse::class)->getBySurvey($survey);

        /** @var ClosingDetailsState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ClosingDetailsState());
        if (is_null($formWizard->getSubject())) {
            $formWizard->setSubject($response);
        }
        // ToDo: replace this with our own merge, or make the form wizard store an array of changes until we're ready to flush
        $formWizard->getSubject()->setSurvey($survey);
        $formWizard->getSubject()->setVehicle($this->entityManager->merge($formWizard->getSubject()->getVehicle()));
        $formWizard->setSubject($this->entityManager->merge($formWizard->getSubject()));

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::ROUTE_NAME, ['state' => $state]);
    }
}
