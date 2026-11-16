<?php

namespace App\Tests\NewFunctional;

use App\Tests\NewFunctional\AbstractFunctionalTestCase;
use App\Tests\NewFunctional\Wizard\Action\Context;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class AbstractWizardTestCase extends AbstractFunctionalTestCase
{
    protected function createContext(string $basePath): Context
    {
        $debugLevel = getenv('DEBUG') ?: 0;
        $entityManager = $this->getContainer()->get('doctrine.orm.entity_manager');

        $output = ($debugLevel > 0) ? new ConsoleOutput() : null;
        $context = new Context($this->client, $entityManager, $this, $output, [
            'basePath' => $basePath,
        ], $debugLevel);

        if ($debugLevel > 0) {
            $dataSetName = $this->getDataSetName() ?? "Data set start";
            $context->output("\n<question>{$dataSetName}</question>\n");
        }
        return $context;
    }

    public function getDataSetName(): ?string
    {
        $name = $this->dataName();

        // Named data sets have a string key; unnamed (numeric) data sets have no usable name.
        return is_string($name) ? $name : null;
    }
}