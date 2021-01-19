<?php

namespace App\Controller\Workflow;

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
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var LoggerInterface
     */
    private $log;

    abstract protected function getFormWizard(): FormWizardStateInterface;
    abstract protected function setFormWizard(FormWizardStateInterface $formWizard);
    abstract protected function cleanUp();

    abstract protected function getRedirectUrl($state): Response;

    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $log)
    {
        $this->entityManager = $entityManager;
        $this->log = $log;
    }

    /**
     * @param WorkflowInterface $stateMachine
     * @param Request $request
     * @param $state
     * @return Response
     * @throws Exception
     */
    protected function doWorkflow(WorkflowInterface $stateMachine, Request $request, $state = null): Response
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
        if (!is_null($form->getClickedButton())) {
            if ($form->isValid()) {
                $transitions = $stateMachine->getEnabledTransitions($formWizard);

                // Filter transitions based on form data (not accessible by guards)
                foreach ($transitions as $k=>$v) {
                    $metadata = $stateMachine->getMetadataStore()->getTransitionMetadata($v);

                    if ($transitionWhenFormData = $metadata['transitionWhenFormData'] ?? false) {
                        $data = $form->get($transitionWhenFormData['property'])->getData();
                        $value = $transitionWhenFormData['value'];

                        if (!(is_array($value) ? in_array($data, $value) : $data === $value)) {
                            unset($transitions[$k]);
                        }
                    }
                }

                $transitions = array_values($transitions);

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

        return $this->render($template, [
            'form' => $form->createView(),
            'subject' => $formWizard->getSubject(),
            'formWizardState' => $formWizard,
        ]);
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

        $metadata = $stateMachine->getMetadataStore()->getTransitionMetadata($transition);
        $subject = $formWizard->getSubject();

        if ($metadata['persist'] ?? false)
        {
            if (!$this->entityManager->contains($subject)) {
                $this->entityManager->persist($subject);
            };
            $this->preFlush($subject);
            $this->entityManager->flush();
        }

        if ($redirectRoute = $metadata['redirectRoute'] ?? false)
        {
            if (is_array($redirectRoute)) {
                $propertyAccessor = PropertyAccess::createPropertyAccessor();

                // e.g. redirectRoute = ['routeName' => 'app_summary', 'parameterMappings' => ['id' => 'vehicleId']]
                // Would essentially call generateUrl('app_summary', ['id' => $subject->getVehicleId()]);

                $params = array_map(function(string $propertyPath) use ($subject, $propertyAccessor) {
                    return $propertyAccessor->getValue($subject, $propertyPath);
                }, $redirectRoute['parameterMappings'] ?? []);

                $routeName = $redirectRoute['routeName'];
            } else {
                $params = [];
                $routeName = $redirectRoute;
            }

            $this->cleanUp();
            return $this->redirectToRoute($routeName, $params);
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
        $state = $formWizard->getState();
        $formMap = $formWizard->getStateFormMap();

        if (isset($formMap[$state]))
        {
            $formClass = $formMap[$state];
            $formOptions = [];

            if (is_array($formClass)) {
                $formOptions = $formClass['options'];
                $formClass = $formClass['form'];
            }

            $form = $this->createForm($formClass, $formWizard->getSubject(), $formOptions);
        } else {
            $form = $this->createFormBuilder()->getForm();
        }

        // If we have only one possible transition, and it is meant to persist/flush
        // then we want to have a better label for the "continue" button
        $buttonLabel = null;
        $transitions = $stateMachine->getEnabledTransitions($formWizard);
        if (count($transitions) === 1) {
            $transition = $transitions[array_key_last($transitions)];
            $metadata = $stateMachine->getMetadataStore()->getTransitionMetadata($transition);
            $isSavePoint = ($metadata['persist'] ?? false) === true;
            $buttonLabel = $metadata['buttonLabel'] ?? ($isSavePoint ? 'Save and finish' : null);
        }
        $template = $formWizard->getStateTemplateMap()[$state] ?? $formWizard->getDefaultTemplate();
        $form->add('continue', ButtonType::class, [
            'type' => 'submit',
            'label' => $buttonLabel,
        ]);

        return [$form, $template];
    }

    protected function preFlush($subject)
    {
        // Overridable hook point
    }
}
