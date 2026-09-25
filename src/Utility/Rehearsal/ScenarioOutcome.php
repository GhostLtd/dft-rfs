<?php

namespace App\Utility\Rehearsal;

final class ScenarioOutcome
{
    public const string PASS = '✅';
    public const string FAIL = '❌';
    public const string WAIT = '⏳';

    /** @param array<string, string> $found row key ("<type>/<channel>") => latest Notify status */
    public function __construct(
        public readonly Scenario $scenario,
        public readonly string $surveyId,
        public readonly array $found,
        public readonly string $verdict,
        public readonly string $detail,
    ) {}
}
