<?php


namespace App\Workflow;


use Doctrine\ORM\EntityManagerInterface;
use Ghost\GovUkFrontendBundle\Form\Type\ButtonType;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class FormWizardManager
{
    private $formFactory;
    private $router;
    private $entityManager;

    public function __construct(FormFactoryInterface $formFactory, RouterInterface $router, EntityManagerInterface $entityManager)
    {
        $this->formFactory = $formFactory;
        $this->router = $router;
        $this->entityManager = $entityManager;
    }

    public function getFilteredTransitions(WorkflowInterface $stateMachine, FormWizardStateInterface $formWizard, FormInterface $form)
    {
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

        return array_values($transitions);
    }

    public function createForm(FormWizardStateInterface $formWizard, WorkflowInterface $stateMachine, $showCancelButton = true)
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

            $form = $this->formFactory->create($formClass, $formWizard->getSubject(), $formOptions);
        } else {
            $form = $this->formFactory->createBuilder()->getForm();
        }

        // If we have only one possible transition, and it is meant to persist/flush
        // then we want to have a better label for the "continue" button
        $submitLabel = null;
        $cancelLabel = null;
        $transitions = $stateMachine->getEnabledTransitions($formWizard);
        if (count($transitions) === 1) {
            $transition = $transitions[array_key_last($transitions)];
            $metadata = $stateMachine->getMetadataStore()->getTransitionMetadata($transition);
            $isSavePoint = ($metadata['persist'] ?? false) === true;
            $submitLabel = $metadata['submitLabel'] ?? ($isSavePoint ? 'Save and continue' : null);
            $cancelLabel = $metadata['cancelLabel'] ?? null;
        }
        $form->add('continue', ButtonType::class, [
            'type' => 'submit',
            'label' => $submitLabel,
        ]);
        if ($showCancelButton) {
            $form->add('cancel', ButtonType::class, [
                'type' => 'submit',
                'label' => $cancelLabel,
                'attr' => ['class' => 'govuk-button--secondary'],
            ]);
        }

        return $form;
    }

    public function processTransitionMetadata(array $transitionMetadata, $subject)
    {
        if ($transitionMetadata['persist'] ?? false)
        {
            $this->persistSubject($subject);
        }

        $redirectUrl = null;
        if ($redirectRoute = $transitionMetadata['redirectRoute'] ?? false) {
            $redirectUrl = $this->resolveRedirectRouteForTransition($redirectRoute, $subject);
        }

        return $redirectUrl;
    }

    protected function persistSubject($subject)
    {
        if (!$this->entityManager->contains($subject)) {
            $this->entityManager->persist($subject);
        };
        $this->entityManager->flush();
    }

    protected function resolveRedirectRouteForTransition($redirectRoute, $subject)
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
        return $this->router->generate($routeName, $params);
    }
}