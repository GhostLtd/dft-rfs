<?php

namespace App\Controller\Admin\RoRo;

use App\Entity\RoRo\OperatorGroup;
use App\Repository\RoRo\OperatorRepository;
use App\Workflow\RoRo\OperatorGroupState;
use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkCoreBundle\Controller\FormWizard\AbstractSessionStateFormWizardController;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Ghost\GovUkCoreBundle\FormWizard\Place;
use Ghost\GovUkCoreBundle\FormWizard\PropertyMerger;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route("/operator-groups", name: "admin_operator_groups_")]
class OperatorGroupWizardController extends AbstractSessionStateFormWizardController
{
    private const string MODE_ADD = 'add';
    private const string MODE_EDIT = 'edit';

    protected string $mode;
    protected OperatorGroup $databaseOperatorGroup;

    public function __construct(
        FormWizardManager            $formWizardManager,
        RequestStack                 $requestStack,
        PropertyMerger               $propertyMerger,
        protected EntityManagerInterface $entityManager,
        protected OperatorRepository $operatorRepository,
    ) {
        parent::__construct($formWizardManager, $requestStack, $propertyMerger);
    }

    /**
     * @throws \Exception
     */
    #[Route("/add/{state}", name: "add_place")]
    #[Route("/add", name: "add_start")]
    #[IsGranted("CAN_ADD_OPERATOR_GROUP")]
    public function add(Request $request, ?string $state = null): Response
    {
        $this->mode = self::MODE_ADD;
        $this->databaseOperatorGroup = new OperatorGroup();
        return $this->doWorkflow($request, $state, $this->getAdditionalViewDataClosure());
    }

    /**
     * @throws \Exception
     */
    #[Route("/{operatorGroupId}/edit/{state}", name: "edit_place")]
    #[Route("/{operatorGroupId}/edit", name: "edit_start")]
    #[IsGranted("CAN_EDIT_OPERATOR_GROUP", subject: "operatorGroup")]
    public function edit(
        Request           $request,
        #[MapEntity(expr: "repository.findOneById(operatorGroupId)")]
        OperatorGroup     $operatorGroup,
        ?string           $state = null,
    ): Response
    {
        $this->mode = self::MODE_EDIT;
        $this->databaseOperatorGroup = $operatorGroup;
        return $this->doWorkflow($request, $state, $this->getAdditionalViewDataClosure());
    }

    private function getAdditionalViewDataClosure(): \Closure
    {
        return function (FormWizardStateInterface $state) {
            $name = $state->getSubject()->getName();
            return match ((string) $state->getPlace()) {
                OperatorGroupState::STATE_PREVIEW => [
                    'mode' => $this->mode,
                    'name' => $name,
                    'operatorsInGroup' => $this->operatorRepository->findOperatorsWithNamePrefix($name),
                ],
                default => [
                    'mode' => $this->mode,
                ],
            };
        };
    }

    protected function getState(): FormWizardStateInterface
    {
        /** @var OperatorGroupState|null $state */
        $state = $this->session->get($this->getSessionKey(), null);

        // If there's no session currently in progress, or if the operator group in the session is a different one to
        // the one mentioned in the URL, generate a new state.
        if (!$state || $state->getSubject()->getId() !== $this->databaseOperatorGroup->getId()) {
            $state = (new OperatorGroupState())
                ->setSubject($this->databaseOperatorGroup)
                ->setMode($this->mode);
        }

        $formData = $state->getSubject();
        $this->databaseOperatorGroup->merge($formData);
        $state->setSubject($this->databaseOperatorGroup);

        return $state;
    }

    protected function getRedirectResponse(?Place $place): RedirectResponse
    {
        return match ($this->mode) {
            self::MODE_ADD => new RedirectResponse(
                $this->generateUrl('admin_operator_groups_add_place', ['state' => (string) $place])
            ),
            self::MODE_EDIT => new RedirectResponse(
                $this->generateUrl('admin_operator_groups_edit_place', [
                    'operatorGroupId' => $this->databaseOperatorGroup->getId(),
                    'state' => (string) $place,
                ])
            ),
        };
    }

    protected function getCancelRedirectResponse(): ?RedirectResponse
    {
        return match ($this->mode) {
            self::MODE_ADD => new RedirectResponse($this->generateUrl('admin_operator_groups_list')),
            self::MODE_EDIT => new RedirectResponse($this->generateUrl('admin_operator_groups_view', [
                'operatorGroupId' => $this->databaseOperatorGroup->getId(),
            ])),
        };
    }
}
