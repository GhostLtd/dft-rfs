<?php

namespace App\Tests\Functional\Wizard;

class WizardEndTestCase implements WizardTestCase
{
    public const MODE_CONTAINS = 'contains';
    public const MODE_EXACT = 'exact';

    protected string $expectedTitle;
    protected string $matchMode;

    public function __construct(string $expectedTitle, string $matchMode=self::MODE_EXACT)
    {
        $this->expectedTitle = $expectedTitle;
        $this->matchMode = $matchMode;

        if (!in_array($matchMode, [self::MODE_CONTAINS, self::MODE_EXACT])) {
            throw new \RuntimeException("Invalid matchMode: '{$matchMode}'");
        }
    }

    public function getExpectedTitle(): string
    {
        return $this->expectedTitle;
    }

    public function getMatchMode(): string
    {
        return $this->matchMode;
    }
}