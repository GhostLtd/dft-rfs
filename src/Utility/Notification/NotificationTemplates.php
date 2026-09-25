<?php

namespace App\Utility\Notification;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\Common\Util\ClassUtils;

/**
 * Maps each survey class and notification type to Notify template IDs by channel.
 * Channels without a template are omitted.
 */
final class NotificationTemplates
{
    /** @var array<class-string, array<string, array<string, string>>>  survey class => type value => channel value => template id */
    private const array TEMPLATES = [
        DomesticSurvey::class => [
            'invite'          => [NotificationChannel::LETTER->value => Reference::LETTER_DOMESTIC_SURVEY_INVITE,     NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_INVITE],
            'reminder-1'      => [NotificationChannel::LETTER->value => Reference::LETTER_DOMESTIC_SURVEY_REMINDER_1, NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_REMINDER_1],
            'reminder-2'      => [NotificationChannel::LETTER->value => Reference::LETTER_DOMESTIC_SURVEY_REMINDER_2, NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_REMINDER_2],
            'reminder-manual' => [NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_MANUAL_REMINDER],
            'in-progress-reminder-1' => [NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_1, NotificationChannel::SMS->value => Reference::SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_1],
            'in-progress-reminder-2' => [NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_2, NotificationChannel::SMS->value => Reference::SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_2],
            'in-progress-reminder-3' => [NotificationChannel::EMAIL->value => Reference::EMAIL_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_3, NotificationChannel::SMS->value => Reference::SMS_DOMESTIC_SURVEY_IN_PROGRESS_REMINDER_3],
        ],
        InternationalSurvey::class => [
            'invite'          => [NotificationChannel::LETTER->value => Reference::LETTER_INTERNATIONAL_SURVEY_INVITE,     NotificationChannel::EMAIL->value => Reference::EMAIL_INTERNATIONAL_SURVEY_INVITE],
            'reminder-1'      => [NotificationChannel::LETTER->value => Reference::LETTER_INTERNATIONAL_SURVEY_REMINDER_1, NotificationChannel::EMAIL->value => Reference::EMAIL_INTERNATIONAL_SURVEY_REMINDER_1],
            'reminder-2'      => [NotificationChannel::LETTER->value => Reference::LETTER_INTERNATIONAL_SURVEY_REMINDER_2, NotificationChannel::EMAIL->value => Reference::EMAIL_INTERNATIONAL_SURVEY_REMINDER_2],
            'reminder-manual' => [NotificationChannel::EMAIL->value => Reference::EMAIL_INTERNATIONAL_SURVEY_MANUAL_REMINDER],
        ],
        // Letter only: there are no pre-enquiry email templates, so no email is ever sent for pre-enquiries.
        PreEnquiry::class => [
            'invite'     => [NotificationChannel::LETTER->value => Reference::LETTER_PRE_ENQUIRY_INVITE],
            'reminder-1' => [NotificationChannel::LETTER->value => Reference::LETTER_PRE_ENQUIRY_REMINDER_1],
            'reminder-2' => [NotificationChannel::LETTER->value => Reference::LETTER_PRE_ENQUIRY_REMINDER_2],
        ],
        RoRoSurvey::class => [
            'reminder-1' => [NotificationChannel::EMAIL->value => Reference::EMAIL_RORO_REMINDER_1],
            'reminder-2' => [NotificationChannel::EMAIL->value => Reference::EMAIL_RORO_REMINDER_2],
        ],
    ];

    /**
     * Returns template IDs keyed by channel, or an empty array for an unsupported type.
     *
     * @return array<string, string>
     */
    public static function for(string $surveyClass, NotificationType $type): array
    {
        // Use the mapped survey class when the caller passes a Doctrine proxy.
        return self::TEMPLATES[ClassUtils::getRealClass($surveyClass)][$type->value] ?? [];
    }

    /**
     * Returns whether the survey class has at least one template for this notification type.
     */
    public static function has(string $surveyClass, NotificationType $type): bool
    {
        return self::for($surveyClass, $type) !== [];
    }

    /**
     * Returns supported notification types in the admin display order.
     *
     * @return list<NotificationType>
     */
    public static function typesFor(string $surveyClass): array
    {
        return array_values(array_filter(NotificationType::cases(), fn(NotificationType $t) => self::has($surveyClass, $t)));
    }
}
