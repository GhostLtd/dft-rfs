<?php

namespace App\Controller\Workflow;

use App\Workflow\FormWizardInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;

abstract class AbstractWorkflowController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    abstract protected function getFormWizard(): FormWizardInterface;
    abstract protected function setFormWizard(FormWizardInterface $formWizard);
    abstract protected function cleanUp();

    abstract protected function getRouteName(): string;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
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

        if (is_null($state)) return $this->redirectToRoute($this->getRouteName(), ['state' => $formWizard->getState()]);

        if ($state !== $formWizard->getState()) {
            if ($formWizard->isValidJumpInState($state))
            {
                $formWizard->setState($state);
            } else {
                return $this->redirectToRoute($this->getRouteName(), ['state' => $formWizard->getState()]);
            }
        }

        /** @var FormInterface $form */
        list($form, $template) = $this->getFormAndTemplate($formWizard);

        $form->handleRequest($request);

        // validate/state-transition
        if (!is_null($form->getClickedButton()))
        {
            if ($form->isValid())
            {
                $transitions = $stateMachine->getEnabledTransitions($formWizard);

                foreach ($transitions as $k=>$v)
                {
                    if ($transitionWhenFormData = $stateMachine->getMetadataStore()->getTransitionMetadata($v)['transitionWhenFormData'] ?? false)
                    {
                        if (
                            is_array($transitionWhenFormData['value'])
                                ? in_array($form->get($transitionWhenFormData['property'])->getData(), $transitionWhenFormData['value'])
                                : $form->get($transitionWhenFormData['property'])->getData() === $transitionWhenFormData['value']
                        ) {
                            return $this->applyTransitionAndRedirect($request, $stateMachine, $formWizard, $v);
                        } else {
                            unset($transitions[$k]);
                        }
                    }
                }
                $transitions = array_values($transitions);

                if (count($transitions) === 1)
                {
                    return $this->applyTransitionAndRedirect($request, $stateMachine, $formWizard, $transitions[0]);
                }
            }
        }

        return $this->render($template, [
            'form' => $form->createView(),
            'subject' => $formWizard->getSubject(),
        ]);
    }

    /**
     * @param Request $request
     * @param WorkflowInterface $stateMachine
     * @param FormWizardInterface $formWizard
     * @param Transition $transition
     * @return Response
     * @throws Exception
     */
    private function applyTransitionAndRedirect(Request $request, WorkflowInterface $stateMachine, FormWizardInterface $formWizard, Transition $transition)
    {
        $stateMachine->apply($formWizard, $transition->getName());
        $this->setFormWizard($formWizard);

        if ($alternativeRoute = $stateMachine->getMetadataStore()->getTransitionMetadata($transition)['persist'] ?? false)
        {
            if (!$this->entityManager->contains($formWizard->getSubject())) {
                throw new Exception("Subject not managed by ORM");
            };
            $this->entityManager->flush();
        }

        if ($alternativeRoute = $stateMachine->getMetadataStore()->getTransitionMetadata($transition)['redirectRoute'] ?? false)
        {
            $this->cleanUp();
            return $this->redirectToRoute($alternativeRoute);
        }

        return $this->redirectToRoute($this->getRouteName(), ['state' => $formWizard->getState()]);
    }

    /**
     * @param FormWizardInterface $formWizard
     * @return array
     */
    protected function getFormAndTemplate(FormWizardInterface $formWizard)
    {
        $state = $formWizard->getState();
        $formMap = $formWizard->getStateFormMap();

        if (isset($formMap[$state]))
        {
            $form = $this->createForm($formMap[$state]);
            if ($form->getConfig()->getDataClass())
            {
                $form->setData($formWizard->getSubject());
            }
        } else {
            $form = $this->createFormBuilder()
                ->getForm();
        }
        $template = $formWizard->getStateTemplateMap()[$state] ?? $formWizard->getDefaultTemplate();
        $form->add('continue', ButtonType::class, ['type' => 'submit']);

        return [$form, $template];
    }
}
