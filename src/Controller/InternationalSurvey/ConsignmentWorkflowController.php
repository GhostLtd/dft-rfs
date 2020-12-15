<?php

namespace App\Controller\InternationalSurvey;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\International\Consignment;
use App\Entity\International\Stop;
use App\Entity\International\SurveyResponse;
use App\Entity\International\Trip;
use App\Entity\International\Vehicle;
use App\Repository\International\ConsignmentRepository;
use App\Workflow\FormWizardInterface;
use App\Workflow\InternationalSurvey\ConsignmentState;
use App\Workflow\InternationalSurvey\VehicleState;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Entity;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * @Route("/international-survey/trips/{tripId}/consignment/{consignmentId}")
 * @Entity("consignment", expr="repository.workflowParamConverter(consignmentId)")
 * @Entity("trip", expr="repository.find(tripId)")
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

    /**
     * @Route("/add-another", name=self::ADD_ANOTHER_ROUTE)
     * @param Consignment|null $consignment
     * @param Trip|null $trip
     * @param null $state
     * @return Response
     */
    public function addAnother(Consignment $consignment = null, Trip $trip = null, $state = null): Response
    {
        $this->cleanUp();
        return $this->redirectToRoute(self::START_ROUTE, ['tripId' => $trip->getId(), 'consignmentId' => 'add']);
    }


    /**
     * @Route("/state-{state}", name=self::WIZARD_ROUTE)
     * @Route("", name=self::START_ROUTE)
     * @param WorkflowInterface $internationalSurveyConsignmentStateMachine
     * @param Request $request
     * @param Consignment|null $consignment
     * @param Trip|null $trip
     * @param null $state
     * @return Response
     * @throws Exception
     */
    public function index(WorkflowInterface $internationalSurveyConsignmentStateMachine,
                          Request $request,
                          Consignment $consignment = null,
                          Trip $trip = null,
                          $state = null): Response
    {
        $this->consignment = $consignment;
        $this->trip = $trip;
        return $this->doWorkflow($internationalSurveyConsignmentStateMachine, $request, $state);
    }

    protected function getFormWizard(): FormWizardInterface
    {
        /** @var ConsignmentState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), new ConsignmentState());

        $consignment = $formWizard->getSubject() ?? $this->consignment;
        $this->consignment->mergeChanges($consignment, $this->getDoctrine()->getRepository(Stop::class));
        if (!$this->consignment->getTrip()) $this->consignment->setTrip($this->trip);
        $formWizard->setSubject($this->consignment);

        return $formWizard;
    }

    protected function getRedirectUrl($state): Response
    {
        return $this->redirectToRoute(self::WIZARD_ROUTE, ['tripId' => $this->trip->getId(), 'consignmentId' => $this->consignment->getId() ?? 'add', 'state' => $state]);
    }
}
