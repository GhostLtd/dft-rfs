<?php

namespace App\Utility;

use App\Entity\Domestic\Survey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Features;
use App\Form\Validator\Postcode;
use Psr\Log\LoggerInterface;

class PostcodeChangeoverHelper
{
    /** Whether IRHS trip origin/destination accept an outward code on its own (e.g. "NN6") in postcode-only mode */
    public const bool IRHS_ALLOW_PARTIAL_POSTCODE = true;

    public function __construct(
        private LoggerInterface $logger,
        private ?string $changeoverDate = null,
        private ?string $irhsChangeoverDate = null,
    ) {
    }

    /**
     * CSRGT: whether origin/destination/furthest-stop fields are postcode-only for this survey.
     */
    public function isPostcodeOnly(?Survey $survey): bool
    {
        // Feature disabled → always old mode (kill switch)
        if (!Features::isEnabled(Features::POSTCODE_CHANGEOVER)) {
            return false;
        }

        // Northern Ireland surveys never use postcode-only mode (their journeys may cross into the Republic of Ireland)
        if ($survey?->getIsNorthernIreland()) {
            return false;
        }

        return $this->isOnOrAfterChangeover($survey?->getSurveyPeriodStart(), $this->changeoverDate, 'APP_POSTCODE_CHANGEOVER_DATE');
    }

    /**
     * IRHS: whether trip origin/destination are postcode-only for this survey.
     */
    public function isIrhsPostcodeOnly(?InternationalSurvey $survey): bool
    {
        if (!Features::isEnabled(Features::IRHS_POSTCODE_CHANGEOVER)) {
            return false;
        }

        return $this->isOnOrAfterChangeover($survey?->getSurveyPeriodStart(), $this->irhsChangeoverDate, 'APP_IRHS_POSTCODE_CHANGEOVER_DATE');
    }

    /**
     * The constraint applied to IRHS trip origin/destination in postcode-only mode. Shared by the frontend and admin
     * forms and by the pre-enable check command so they can't drift.
     *
     * $groups is deliberately required: Postcode passes it straight to Constraint, so [] means "no groups" and a plain
     * $validator->validate($value, $constraint) would silently skip it. Callers validating directly should pass ['Default'].
     *
     * @param array<string> $groups
     */
    public static function createIrhsPostcodeConstraint(array $groups): Postcode
    {
        return new Postcode(allowPartial: self::IRHS_ALLOW_PARTIAL_POSTCODE, checkOutwardCode: true, groups: $groups);
    }

    private function isOnOrAfterChangeover(?\DateTime $surveyPeriodStart, ?string $changeoverDate, string $envVarName): bool
    {
        // Null survey or null surveyPeriodStart → old mode (safe default)
        if ($surveyPeriodStart === null) {
            return false;
        }

        // Feature enabled + no changeover date → new mode for all surveys
        if ($changeoverDate === null || $changeoverDate === '') {
            return true;
        }

        // Feature enabled + changeover date → compare survey's surveyPeriodStart
        try {
            $changeover = new \DateTime($changeoverDate);
        } catch (\Exception) {
            $this->logger->error("Invalid {$envVarName}: '{$changeoverDate}' — postcode feature disabled");
            return false;
        }
        return $surveyPeriodStart >= $changeover;
    }
}
