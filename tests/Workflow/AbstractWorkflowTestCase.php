<?php

namespace App\Tests\Workflow;

use App\Workflow\FormWizardStateInterface;
use App\Workflow\InternationalSurvey\TripState;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

class AbstractWorkflowTestCase extends WebTestCase
{
    protected Registry $workflowRegistry;
    protected FormWizardStateInterface $workflowState;
    protected WorkflowInterface $workflow;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();

        $container = static::getContainer()->get('test.service_container');
        $this->workflowRegistry = $container->get('workflow.registry');
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
        $this->assertEquals($state, $this->workflowState->getState());
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