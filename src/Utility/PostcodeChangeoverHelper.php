<?php

namespace App\Utility;

use App\Entity\Domestic\Survey;
use App\Features;
use Psr\Log\LoggerInterface;

class PostcodeChangeoverHelper
{
    private ?string $changeoverDate;

    public function __construct(
        private LoggerInterface $logger,
        ?string $changeoverDate = null,
    ) {
        $this->changeoverDate = $changeoverDate;
    }

    public function isPostcodeOnly(?Survey $survey): bool
    {
        // Feature disabled → always old mode (kill switch)
        if (!Features::isEnabled(Features::POSTCODE_CHANGEOVER)) {
            return false;
        }

        // Null survey or null surveyPeriodStart → old mode (safe default)
        if ($survey === null || $survey->getSurveyPeriodStart() === null) {
            return false;
        }

        // Northern Ireland surveys never use postcode-only mode
        if ($survey->getIsNorthernIreland()) {
            return false;
        }

        // Feature enabled + no changeover date → new mode for all surveys
        if ($this->changeoverDate === null || $this->changeoverDate === '') {
            return true;
        }

        // Feature enabled + changeover date → compare survey's surveyPeriodStart
        try {
            $changeover = new \DateTime($this->changeoverDate);
        } catch (\Exception $e) {
            $this->logger->error("Invalid APP_POSTCODE_CHANGEOVER_DATE: '{$this->changeoverDate}' — postcode feature disabled");
            return false;
        }
        return $survey->getSurveyPeriodStart() >= $changeover;
    }
}
