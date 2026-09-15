<?php
namespace App\Tests\Utility;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\NotifyApiResponse;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use App\Utility\NotifyApiResponseCleaner;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Email;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Letter;
use PHPUnit\Framework\TestCase;

class NotifyApiResponseCleanerTest extends TestCase
{
    public function testLetterContentStrippedAcrossNotifications(): void
    {
        $survey = new Survey();
        $letterRow = (new SurveyNotification())->setType(NotificationType::INVITE)->setChannel(NotificationChannel::LETTER)->setSentAt(new \DateTime());
        $emailRow = (new SurveyNotification())->setType(NotificationType::INVITE)->setChannel(NotificationChannel::EMAIL)->setSentAt(new \DateTime());
        $survey->addNotification($letterRow)->addNotification($emailRow);

        $letterRow->addNotifyApiResponse((new NotifyApiResponse())->setMessageClass(Letter::class)->setData(['id' => 1, 'content' => ['body' => 'Dear...']]));
        $emailRow->addNotifyApiResponse((new NotifyApiResponse())->setMessageClass(Email::class)->setData(['id' => 2, 'content' => ['body' => 'kept']]));

        self::assertFalse(NotifyApiResponseCleaner::surveyNotifyApiResponsesHaveBeenCleaned($survey));
        NotifyApiResponseCleaner::cleanSurveyNotifyApiResponses($survey);
        self::assertTrue(NotifyApiResponseCleaner::surveyNotifyApiResponsesHaveBeenCleaned($survey));

        self::assertSame(['id' => 1], $letterRow->getNotifyApiResponses()->first()->getData());
        self::assertArrayHasKey('content', $emailRow->getNotifyApiResponses()->first()->getData(), 'email content is not personal-data-cleaned today; unchanged');
    }
}
