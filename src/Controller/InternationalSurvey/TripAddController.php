<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Repository\International\VehicleRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\TripState;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class TripAddController extends AbstractSessionStateWorkflowController
{
    use SurveyHelperTrait;

    public const START_ROUTE = 'app_internationalsurvey_trip_add_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_trip_add_state';

    protected $surveyResponse;

    /** @var Vehicle */
    protected $vehicle;

    protected $vehicleRepository;

    public function __construct(VehicleRepository $vehicleRepository, EntityManagerInterface $entityManager, LoggerInterface $logger, SessionInterface $session)
    {
        parent::__construct($entityManager, $logger, $session);
        $this->vehicleRepository = $vehicleRepository;
    }

    /**
     * @Route("/international-survey/vehicles/{vehicleId}/add-trip/{state}", name=self::WIZARD_ROUTE)
     * @Route("/international-survey/vehicles/{vehicleId}/add-trip", name=self::START_ROUTE)
     */
    public function index(WorkflowInterface $internationalSurveyTripStateMachine,
                          Request $request,
                          UserInterface $user,
                          string $vehicleId,
                          ?string $state = null): Response
    {
        $this->surveyResponse = $this->getSurveyResponse($user);
        $this->vehicle = $this->vehicleRepository->findByIdAndSurveyResponse($vehicleId, $this->surveyResponse);

        if (!$this->vehicle) {
            throw new NotFoundHttpException();
        }

        return $this->doWorkflow($internationalSurveyTripStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var FormWizardInterface $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new TripState());

        $this->setSubjectOnWizard($this->vehicle, $formWizard);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['vehicleId' => $this->vehicle->getId(), 'state' => $state]);
    }

    protected function setSubjectOnWizard(Vehicle $vehicle, FormWizardInterface $formWizard): void
    {
        $trip = $formWizard->getSubject() ?? new Trip();
        $trip->setVehicle($vehicle);
        $formWizard->setSubject($trip);
    }
}
