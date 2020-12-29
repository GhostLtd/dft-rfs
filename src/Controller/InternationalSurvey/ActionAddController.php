<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Action;
use App\Entity\International\Trip;
use App\Repository\International\TripRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\ActionState;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class ActionAddController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_action_add_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_action_add_state';

    protected $surveyResponse;

    /** @var Trip */
    protected $trip;

    protected $tripRepository;

    public function __construct(TripRepository $tripRepository, EntityManagerInterface $entityManager, SessionInterface $session)
    {
        parent::__construct($entityManager, $session);
        $this->tripRepository = $tripRepository;
    }

    /**
     * @Route("/international-survey/trips/{tripId}/add-action/{state}", name=self::WIZARD_ROUTE)
     * @Route("/international-survey/trips/{tripId}/add-action", name=self::START_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyActionStateMachine,
                          Request $request,
                          UserInterface $user,
                          string $tripId,
                          ?string $state = null): Response
    {
        $this->surveyResponse = $this->getSurveyResponse($user);
        $this->trip = $this->tripRepository->findByIdAndSurveyResponse($tripId, $this->surveyResponse);

        if (!$this->trip) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyActionStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ActionState());

        $this->setSubjectOnWizard($formWizard);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['tripId' => $this->trip->getId(), 'state' => $state]);
    }

    protected function setSubjectOnWizard(FormWizardInterface $formWizard): void
    {
        $action = $formWizard->getSubject() ?? new Action();
        $action->setTrip($this->trip);
        $this->entityManager->persist($action);
        $formWizard->setSubject($action);
    }
}
