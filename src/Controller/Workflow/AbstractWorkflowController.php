<?php

namespace App\Controller\Workflow;

use App\Workflow\FormWizardManager;
use App\Workflow\FormWizardStateInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

abstract class AbstractWorkflowController extends AbstractController
{
    protected $entityManager;
    private $log;
    private $formWizardManager;

    abstract protected function getFormWizard(): FormWizardStateInterface;
    abstract protected function setFormWizard(FormWizardStateInterface $formWizard);
    abstract protected function cleanUp();

    abstract protected function getRedirectUrl($state): Response;
    abstract protected function getCancelUrl(): ?Response;

    public function __construct(FormWizardManager $formWizardManager, EntityManagerInterface $entityManager, LoggerInterface $log)
    {
        $this->entityManager = $entityManager;
        $this->log = $log;
        $this->formWizardManager = $formWizardManager;
    }

    /**
     * @param WorkflowInterface $stateMachine
     * @param Request $request
     * @param $state
     * @return Response
     * @throws Exception
     */
    protected function doWorkflow(WorkflowInterface $stateMachine, Request $request, $state = null, $additionalViewData = []): Response
    {
        $formWizard = $this->getFormWizard();
        if (is_null($state)) return $this->getRedirectUrl($stateMachine->getDefinition()->getInitialPlaces()[0]);

        if ($state !== $formWizard->getState()) {
            if ($formWizard->isValidHistoryState($state)
                || $formWizard->isValidAlternativeStartState($state)
                || $state === $stateMachine->getDefinition()->getInitialPlaces()[0]
            )
            {
                $formWizard->setState($state);
            } else {
                // possibly combination of back link and history back
                // send them to the last allowable history || initial place
                return $this->getRedirectUrl($formWizard->getPreviousHistoryState() ?? $stateMachine->getDefinition()->getInitialPlaces()[0]);
            }
        }

        /** @var FormInterface $form */
        list($form, $template) = $this->getFormAndTemplate($formWizard, $stateMachine);

        $form->handleRequest($request);

        // validate/state-transition
        if (!is_null($button = $form->getClickedButton())) {
            if ($button->getName() === 'cancel') {
                $url = $this->getCancelUrl();
                if ($url) return $url;
            }
            if ($form->isValid()) {
                $transitions = $this->formWizardManager->getFilteredTransitions($stateMachine, $formWizard, $form);

                if (count($transitions) === 1)
                {
                    return $this->applyTransitionAndRedirect($request, $stateMachine, $formWizard, $transitions[0]);
                } else {
                    // log the fact we encountered two or more transitions
                    $transitionNames = join(", ", array_map(function(Transition $a){return $a->getName();}, $transitions));
                    $this->log->alert("[FormWizard] Multiple transitions on {$request->getPathInfo()}: {$transitionNames}");
                }
            }
        }

        if (!$template) {
            throw new RuntimeException("Template not defined for state '{$state}' of '{$stateMachine->getName()}' state machine");
        }

        return $this->render($template, array_merge($additionalViewData, [
            'form' => $form->createView(),
            'subject' => $formWizard->getSubject(),
            'formWizardState' => $formWizard,
        ]));
    }

    /**
     * @param Request $request
     * @param WorkflowInterface $stateMachine
     * @param FormWizardStateInterface $formWizard
     * @param Transition $transition
     * @return Response
     * @throws Exception
     */
    protected function applyTransitionAndRedirect(Request $request, WorkflowInterface $stateMachine, FormWizardStateInterface $formWizard, Transition $transition)
    {
        $stateMachine->apply($formWizard, $transition->getName());
        $this->setFormWizard($formWizard);

        if ($redirectUrl = $this->formWizardManager->processTransitionMetadata(
            $stateMachine->getMetadataStore()->getTransitionMetadata($transition),
            $formWizard->getSubject()
        )) {
            $this->cleanUp();
            return $this->redirect($redirectUrl);
        }

        return $this->getRedirectUrl($formWizard->getState());
    }

    /**
     * @param FormWizardStateInterface $formWizard
     * @param WorkflowInterface $stateMachine
     * @return array
     */
    protected function getFormAndTemplate(FormWizardStateInterface $formWizard, WorkflowInterface $stateMachine)
    {
        $form = $this->formWizardManager->createForm($formWizard, $stateMachine, $this->getCancelUrl() !== null);
        $template = $formWizard->getStateTemplateMap()[$formWizard->getState()] ?? $formWizard->getDefaultTemplate();

        return [$form, $template];
    }
}
