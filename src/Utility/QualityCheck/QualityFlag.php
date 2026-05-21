<?php

namespace App\Utility\QualityCheck;

readonly class QualityFlag
{
    /**
     * @param array<string, mixed> $payload  Rule-specific extras, also passed as
     *                                       ICU parameters to the reason translation
     *                                       (e.g. ['day_number' => 2]).
     */
    public function __construct(
        public SubjectType $subjectType,
        public string $subjectId,
        public string $reasonCode,
        public array $payload = [],
    ) {}

    /**
     * @param int[] $stopNumbers
     */
    public static function forStops(string $dayId, string $reasonCode, array $stopNumbers): self
    {
        sort($stopNumbers);

        return new self(
            subjectType: SubjectType::DAY,
            subjectId: $dayId,
            reasonCode: $reasonCode,
            payload: ['count' => count($stopNumbers), 'numbers' => implode(',', $stopNumbers)],
        );
    }
}
