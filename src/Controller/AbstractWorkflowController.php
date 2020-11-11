<?php

namespace App\Controller;

use App\Workflow\FormWizardInterface;
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
    public const SESSION_KEY = 'wizard.' . self::class;

    abstract protected function getFormWizard(Request $request): FormWizardInterface;
    abstract protected function getRouteName(): string;
    abstract protected function getDefaultTemplate(): string;

    /**
     * @param WorkflowInterface $stateMachine
     * @param Request $request
     * @param $state
     * @return Response
     * @throws Exception
     */
    protected function doWorkflow(WorkflowInterface $stateMachine, Request $request, $state = null): Response
    {
        $formWizard = $this->getFormWizard($request);

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
        $request->getSession()->set(self::SESSION_KEY, $formWizard);

        if ($alternativeRoute = $stateMachine->getMetadataStore()->getTransitionMetadata($transition)['persist'] ?? false)
        {
            if (!$this->getDoctrine()->getManager()->contains($formWizard->getSubject())) {
                throw new Exception("Subject not managed by ORM");
            };
            $this->getDoctrine()->getManager()->flush();
        }

        if ($alternativeRoute = $stateMachine->getMetadataStore()->getTransitionMetadata($transition)['redirectRoute'] ?? false)
        {
            $request->getSession()->remove(self::SESSION_KEY);
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
        $template = $formWizard->getStateTemplateMap()[$state] ?? $this->getDefaultTemplate();
        $form->add('continue', ButtonType::class, ['type' => 'submit']);

        return [$form, $template];
    }
}
