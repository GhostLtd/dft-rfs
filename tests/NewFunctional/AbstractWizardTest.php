<?php

namespace App\Tests\NewFunctional;

use App\Tests\NewFunctional\AbstractFunctionalTestCase;
use App\Tests\NewFunctional\Wizard\Action\Context;
use App\Tests\NewFunctional\Wizard\Action\WizardAction;
use Symfony\Component\Console\Output\ConsoleOutput;

abstract class AbstractWizardTest extends AbstractFunctionalTestCase
{
    protected function createContext(string $basePath): Context
    {
        $debugLevel = getenv('DEBUG') ?? 0;
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
        return preg_match('/^ with data set "(.*)"$/', $this->getDataSetAsString(false), $matches) ?
            $matches[1] : null;
    }
}