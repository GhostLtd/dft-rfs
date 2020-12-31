<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Consignment;
use App\Entity\International\Trip;
use App\Repository\International\StopRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\ConsignmentState;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Route("/international-survey/trips/{tripId}/consignment")
 * @Entity("trip", expr="repository.find(tripId)")
 * @Security("is_feature_enabled('IRHS_CONSIGNMENTS_AND_STOPS')")
 */
class ConsignmentWorkflowController extends AbstractSessionStateWorkflowController
{
    public const ADD_ANOTHER_ROUTE = 'app_internationalsurvey_consignment_addanother';
    public const START_ROUTE = 'app_internationalsurvey_consignment_start';
    public const WIZARD_ROUTE = 'app_internationalsurvey_consignment_state';

    /**
     * @var Consignment|null
     */
    private $consignment;

    /**
     * @var Trip
     */
    private $trip;

    protected $stopRepository;

    public function __construct(StopRepository $stopRepository, EntityManagerInterface $entityManager, LoggerInterface $logger, SessionInterface $session)
    {
        parent::__construct($entityManager, $logger, $session);
        $this->stopRepository = $stopRepository;
    }

    /**
     * @Route("/add-another", name=self::ADD_ANOTHER_ROUTE)
     */
    public function addAnother(Trip $trip = null): Response
    {
        if ($trip === null) {
            throw new NotFoundHttpException();
        }

        $this->cleanUp();
        return $this->redirectToRoute(self::START_ROUTE, ['tripId' => $trip->getId(), 'consignmentId' => 'add']);
    }

    /**
     * @Route("/{consignmentId}/{state}", name=self::WIZARD_ROUTE)
     * @Route("/{consignmentId}/start", name=self::START_ROUTE)
     * @Entity("consignment", expr="repository.workflowParamConverter(consignmentId)")
     */
    public function index(WorkflowInterface $internationalSurveyConsignmentStateMachine,
                          Request $request,
                          Consignment $consignment = null,
                          Trip $trip = null,
                          $state = null): Response
    {
        if ($trip === null || $consignment === null) {
            throw new NotFoundHttpException();
        }

        $this->consignment = $consignment;
        $this->trip = $trip;
        return $this->doWorkflow($internationalSurveyConsignmentStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var ConsignmentState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ConsignmentState());

        $consignment = $formWizard->getSubject() ?? $this->consignment;
        $this->consignment->mergeChanges($consignment, $this->stopRepository);

        if (!$this->consignment->getTrip()) {
            $this->consignment->setTrip($this->trip);
        }

        $formWizard->setSubject($this->consignment);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['tripId' => $this->trip->getId(), 'consignmentId' => $this->consignment->getId() ?? 'add', 'state' => $state]);
    }
}
