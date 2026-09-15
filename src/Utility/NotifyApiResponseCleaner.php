<?php

namespace App\Utility;

use App\Entity\NotifyApiResponse;
use App\Entity\SurveyNotificationsInterface;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;

class NotifyApiResponseCleaner
{
    public static function cleanSurveyNotifyApiResponses(SurveyNotificationsInterface $survey): void
    {
        foreach (self::letterResponses($survey) as $response) {
            // Removes the "content" row from the api response data
            $response->setData(
                array_filter($response->getData(), fn(string $rowKey) => $rowKey !== 'content', ARRAY_FILTER_USE_KEY)
            );
        }
    }

    public static function surveyNotifyApiResponsesHaveBeenCleaned(SurveyNotificationsInterface $survey): bool
    {
        foreach (self::letterResponses($survey) as $response) {
            if (array_key_exists('content', $response->getData())) {
                return false;
            }
        }

        return true;
    }

    /** @return iterable<NotifyApiResponse> */
    private static function letterResponses(SurveyNotificationsInterface $survey): iterable
    {
        foreach ($survey->getNotifications() as $notification) {
            if (!$notification instanceof ApiResponseInterface) {
                continue;
            }
            foreach ($notification->getNotifyApiResponses() as $response) {
                if ($response->getMessageClass() === Letter::class) {
                    yield $response;
                }
            }
        }
    }
}
