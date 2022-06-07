<?php

namespace App\Utility;

use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Messenger\AlphagovNotify\Letter;

class NotifyApiResponseCleaner
{
    public static function cleanSurveyNotifyApiResponses(ApiResponseInterface $survey)
    {
        $responses = $survey->getNotifyApiResponses();

        foreach($responses as $response) {
            if ($response->getMessageClass() !== Letter::class) {
                continue;
            }

            // Removes the "content" row from the api response data
            $response->setData(
                array_filter($response->getData(), fn(string $rowKey) => $rowKey !== 'content', ARRAY_FILTER_USE_KEY)
            );
        }
    }

    public static function surveyNotifyApiResponsesHaveBeenCleaned(ApiResponseInterface $survey): bool
    {
        $responses = $survey->getNotifyApiResponses();

        foreach($responses as $response) {
            if ($response->getMessageClass() !== Letter::class) {
                continue;
            }

            if (array_key_exists('content', $response->getData())) {
                return false;
            }
        }

        return true;
    }
}