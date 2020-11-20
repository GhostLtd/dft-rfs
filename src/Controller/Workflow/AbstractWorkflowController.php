<?php

namespace App\Controller\Workflow;

use App\Workflow\FormWizardInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use ReflectionClass;
use ReflectionException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

    abstract protected function getRedirectUrl($state): Response;

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

        if (is_null($state)) return $this->getRedirectUrl($formWizard->getState());

        if ($state !== $formWizard->getState()) {
            if ($formWizard->isValidJumpInState($state))
            {
                $formWizard->setState($state);
            } else {
                return $this->getRedirectUrl($formWizard->getState());
            }
        }

        /** @var FormInterface $form */
        list($form, $template) = $this->getFormAndTemplate($formWizard, $stateMachine);

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

        return $this->getRedirectUrl($formWizard->getState());
    }

    /**
     * @param FormWizardInterface $formWizard
     * @param WorkflowInterface $stateMachine
     * @return array
     */
    protected function getFormAndTemplate(FormWizardInterface $formWizard, WorkflowInterface $stateMachine)
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

            $or = new OptionsResolver();
            /** @var FormTypeInterface $formType */
            $formType = new $formClass();
            $formType->configureOptions($or);
            $resolvedOptions = $or->resolve([]);

            $dataClass = $resolvedOptions['data_class'] ?? null;
            if ($dataClass) {
                try {
                    $dataClassRefl = new ReflectionClass($dataClass);
                } catch (ReflectionException $e) {
                    throw new RuntimeException("Invalid data_class specified: '{$dataClass}'");
                }

                // Some use traits, which enables IDE assistance, but isn't valid for the data_class.
                // Override them with the class of the dataSubject.
                if ($dataClassRefl->isTrait()) {
                    $form = $this->createForm(
                        $formClass,
                        $formWizard->getSubject(),
                        array_merge(['data_class' => get_class($formWizard->getSubject())], $formOptions)
                    );
                } else {
                    $form = $this->createForm($formClass, $formWizard->getSubject(), $formOptions);
                }
            }
        } else {
            $form = $this->createFormBuilder()->getForm();
        }

        // If we have only one possible transition, and it is meant to persist/flush
        // then we want to have a better label for the "continue" button
        $isSavePoint = false;
        $transitions = $stateMachine->getEnabledTransitions($formWizard);
        if (count($transitions) === 1) {
            $transition = $transitions[array_key_last($transitions)];
            $metadata = $stateMachine->getMetadataStore()->getTransitionMetadata($transition);
            $isSavePoint = ($metadata['persist'] ?? false) === true;
        }
        $template = $formWizard->getStateTemplateMap()[$state] ?? $formWizard->getDefaultTemplate();
        $form->add('continue', ButtonType::class, [
            'type' => 'submit',
            'label' => $isSavePoint ? 'Save and continue' : null,
        ]);

        return [$form, $template];
    }
}
