<?php

namespace App\Controller\Admin\RoRo;

use App\Controller\Workflow\AbstractSessionStateWorkflowController;
use App\Entity\RoRo\OperatorGroup;
use App\Repository\RoRo\OperatorRepository;
use App\Workflow\FormWizardManager;
use App\Workflow\FormWizardStateInterface;
use App\Workflow\RoRo\OperatorGroupState;
use App\Workflow\RoRo\RoRoState;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Workflow\WorkflowInterface;

#[Route("/operator-groups", name: "admin_operator_groups_")]
class OperatorGroupWizardController extends AbstractSessionStateWorkflowController
{
    private const string MODE_ADD = 'add';
    private const string MODE_EDIT = 'edit';

    protected string $mode;
    protected OperatorGroup $databaseOperatorGroup;

    public function __construct(
        EntityManagerInterface       $entityManager,
        FormWizardManager            $formWizardManager,
        LoggerInterface              $log,
        RequestStack                 $requestStack,
        protected OperatorRepository $operatorRepository,
    )
    {
        parent::__construct($formWizardManager, $entityManager, $log, $requestStack);
    }

    /**
     * @throws \Exception
     */
    #[Route("/add/{state}", name: "add_place")]
    #[Route("/add", name: "add_start")]
    #[IsGranted("CAN_ADD_OPERATOR_GROUP")]
    public function add(WorkflowInterface $operatorGroupStateMachine, Request $request, ?string $state = null): Response
    {
        $this->mode = self::MODE_ADD;
        $this->databaseOperatorGroup = new OperatorGroup();
        return $this->doWorkflow($operatorGroupStateMachine, $request, $state, $this->getAdditionalViewData($state));
    }

    /**
     * @throws \Exception
     */
    #[Route("/{operatorGroupId}/edit/{state}", name: "edit_place")]
    #[Route("/{operatorGroupId}/edit", name: "edit_start")]
    #[IsGranted("CAN_EDIT_OPERATOR_GROUP", subject: "operatorGroup")]
    public function edit(
        WorkflowInterface $operatorGroupStateMachine,
        Request           $request,
        #[MapEntity(expr: "repository.findOneById(operatorGroupId)")]
        OperatorGroup     $operatorGroup,
        ?string           $state = null
    ): Response
    {
        $this->mode = self::MODE_EDIT;
        $this->databaseOperatorGroup = $operatorGroup;
        return $this->doWorkflow($operatorGroupStateMachine, $request, $state, $this->getAdditionalViewData($state));
    }

    protected function getAdditionalViewData(?string $state): array
    {
        $name = $this->getFormWizard()->getSubject()->getName();

        return match ($state) {
            OperatorGroupState::STATE_PREVIEW => [
                'mode' => $this->mode,
                'name' => $name,
                'operatorsInGroup' => $this->operatorRepository->findOperatorsWithNamePrefix($name),
            ],
            default => [
                'mode' => $this->mode,
            ]
        };
    }

    #[\Override]
    protected function getFormWizard(): FormWizardStateInterface
    {
        /** @var RoRoState $formWizard */
        $formWizard = $this->session->get($this->getSessionKey(), null);

        // If there's no session currently in progress, or if the survey in the session is a different survey to the
        // one mentioned in the URL, generate a new state.
        if (!$formWizard || $formWizard->getSubject()->getId() !== $this->databaseOperatorGroup->getId()) {
            $formWizard = (new OperatorGroupState())
                ->setSubject($this->databaseOperatorGroup)
                ->setMode($this->mode);
        }

        $formData = $formWizard->getSubject();

        $this->databaseOperatorGroup->merge($formData);
        $formWizard->setSubject($this->databaseOperatorGroup);
        return $formWizard;
    }

    #[\Override]
    protected function getRedirectUrl($state): Response
    {
        return match ($this->mode) {
            self::MODE_ADD => $this->redirectToRoute('admin_operator_groups_add_place', ['state' => $state]),
            self::MODE_EDIT => $this->redirectToRoute('admin_operator_groups_edit_place', ['operatorGroupId' => $this->databaseOperatorGroup->getId(), 'state' => $state]),
        };
    }

    #[\Override]
    protected function getCancelUrl(): ?Response
    {
        return match ($this->mode) {
            self::MODE_ADD => $this->redirectToRoute('admin_operator_groups_list'),
            self::MODE_EDIT => $this->redirectToRoute('admin_operator_groups_view', ['operatorGroupId' => $this->databaseOperatorGroup->getId()]),
        };
    }
}
