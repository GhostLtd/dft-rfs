<?php

namespace App\Tests\Workflow;

use Ghost\GovUkCoreBundle\EventSubscriber\FormWizard\TransitionPersistSubscriber;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardManager;
use Ghost\GovUkCoreBundle\FormWizard\FormWizardStateInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

class AbstractWorkflowTestCase extends WebTestCase
{
    protected Registry $workflowRegistry;
    protected FormWizardStateInterface $workflowState;
    protected WorkflowInterface $workflow;
    protected FormWizardManager $formWizardManager;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();

        $container = static::getContainer()->get('test.service_container');
        $this->workflowRegistry = $container->get('workflow.registry');
        $this->formWizardManager = $container->get(FormWizardManager::class);

        // These tests build in-memory entity graphs and never flush.
        // Bundle's TransitionPersistSubscriber fires on every workflow.apply()
        // and would try to persist the unflushed graph, breaking the tests.
        // Detach it for the lifetime of the test.
        $dispatcher = $container->get('event_dispatcher');
        $dispatcher->removeListener('workflow.completed', [
            $container->get(TransitionPersistSubscriber::class),
            'onTransition',
        ]);
    }

    protected function setSubjectPersisted($persisted = true): void
    {
        $reflection = new \ReflectionClass($this->workflowState->getSubject());
        $idProp = $reflection->getProperty('id');
        $idProp->setValue($this->workflowState->getSubject(), $persisted ? 'fake-id' : null);
    }

    protected function assertOneTransitionAndApply(): void
    {
        $transitions = $this->workflow->getEnabledTransitions($this->workflowState);
        $this->assertCount(1, $transitions);
        $this->workflow->apply($this->workflowState, $transitions[0]->getName());
    }

    protected function assertStateEquals(string $state): void
    {
        $this->assertEquals($state, (string) $this->workflowState->getPlace());
    }

    protected function assertStateTransitions($states): void
    {
        foreach ($states as $state)
        {
            $this->assertOneTransitionAndApply();
            $this->assertStateEquals($state);
        }
    }
}