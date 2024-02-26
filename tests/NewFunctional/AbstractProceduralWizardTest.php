<?php

namespace App\Tests\NewFunctional;

use App\Tests\NewFunctional\Wizard\Action\AbstractAction;
use App\Tests\NewFunctional\Wizard\Action\CallbackAction;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\FormTestAction;
use App\Tests\NewFunctional\Wizard\Action\PathTestAction;

abstract class AbstractProceduralWizardTest extends AbstractWizardTest
{
    protected Context $context;

    #[\Override]
    public function initialiseClientAndLoadFixtures(array $fixtures, array $pantherOptions = []): void
    {
        parent::initialiseClientAndLoadFixtures($fixtures, array_merge([
            'hostname' => 'rfs-frontend.localhost',
        ], $pantherOptions));
    }

    protected function pathTestAction(string $expectedPath, array $options = []): void
    {
        $this->perform(new PathTestAction($expectedPath, $options));
    }

    protected function callbackTestAction(callable $callback, array $options = []): void
    {
        $action = new CallbackAction($callback);

        if ($options['description'] ?? false) {
            $action->setDescription($options['description']);
        }

        if ($options['descriptionCallback'] ?? false) {
            $action->setDescriptionCallback($options['descriptionCallback']);
        }

        $this->perform($action);
    }

    protected function formTestAction(string $expectedPath, string $submitButtonId = null, array $formTestCases = [], array $options = []): void
    {
        $this->perform(new FormTestAction($expectedPath, $submitButtonId, $formTestCases, $options));
    }

    private function perform(AbstractAction $action): void
    {
        $action->perform($this->context);
        $this->context->increaseActionIndex();
    }
}