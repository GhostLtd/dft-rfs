<?php

namespace App\Tests\NewFunctional\Wizard\Action;

use App\Tests\NewFunctional\AbstractWizardTest;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Panther\Client;

/**
 * Context is a simple mechanism that can be used to:
 * a) Fetch useful test-specific instantiations (client, entityManager, testCase)
 * b) Share data between WizardActions which use callbacks
 */
class Context
{
    protected array $context;

    public function __construct(
        protected Client $client,
        protected EntityManagerInterface $entityManager,
        protected AbstractWizardTest $testCase,
        protected ?OutputInterface $output,
        protected array $config,
        protected int $debugLevel = 0,
    ) {
        $this->context = [
            '_actionIndex' => 0,
        ];
    }

    public function get(?string $key): string
    {
        return $this->context[$key];
    }

    public function all(): array
    {
        return $this->context;
    }

    public function set(string $key, string $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function getTestCase(): AbstractWizardTest
    {
        return $this->testCase;
    }

    public function getOutput(): ?OutputInterface
    {
        return $this->output;
    }

    public function getConfig(string $key)
    {
        return $this->config[$key];
    }


    // Debug helpers

    public function increaseActionIndex(): self
    {
        $this->context['_actionIndex'] += 1;
        $this->output('');
        return $this;
    }

    public function output(string $output, ?int $minDebugLevel=null): self {
        if (!$minDebugLevel || $this->isAtLeastDebugLevel($minDebugLevel)) {
            $this->getOutput()?->writeln($output);
        }
        return $this;
    }

    public function outputHeader(string $header, ?int $minDebugLevel=null): self {
        $actionIndex = str_pad(((intval($this->get('_actionIndex'))) + 1).'.', 4);
        return $this->output("{$actionIndex}<info>{$header}</info>", $minDebugLevel);
    }

    public function outputWithPrefix(string $output, string $prefix='--', ?int $minDebugLevel=null): self
    {
        $paddedPrefix = str_pad($prefix, 2);
        return $this->output("  {$paddedPrefix} {$output}", $minDebugLevel);
    }

    public function isAtLeastDebugLevel(int $debugLevel): bool
    {
        return $this->output && intval($this->debugLevel) >= $debugLevel;
    }
}