<?php

namespace App\Utility;

use App\Entity\SurveyInterface;
use App\Messenger\AlphagovNotify\Letter;

class NotifyApiResponseCleaner
{
    public static function cleanSurveyNotifyApiResponses(SurveyInterface $survey)
    {
        $responses = $survey->getNotifyApiResponses();

        if (!is_array($responses)) {
            return;
        }

        foreach($responses as $key => $response) {
            if (!self::isKeyOfType($key, Letter::class) || !is_array($response)) {
                continue;
            }

            foreach($response as $j => $responseInstance) {
                if (!is_array($responseInstance)) {
                    continue;
                }

                $responses[$key][$j] = array_filter($responseInstance, function ($rowKey) {
                    return $rowKey !== 'content';
                }, ARRAY_FILTER_USE_KEY);
            }
        }

        $survey->setNotifyApiResponse($responses);
    }

    public static function surveyNotifyApiResponsesHaveBeenCleaned(SurveyInterface $survey): bool
    {
        $responses = $survey->getNotifyApiResponses();

        if (is_array($responses)) {
            foreach($responses as $key => $response) {
                if (!self::isKeyOfType($key, Letter::class) || !is_array($response)) {
                    continue;
                }

                foreach($response as $j => $responseInstance) {
                    if (is_array($responseInstance) && array_key_exists('content', $responseInstance)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    protected static function isKeyOfType(string $key, string $class): bool
    {
        return strpos('::'.$key, $class) === (strlen($key) - strlen($class) + 2);
    }
}