<?php

namespace App\Utility\Notification;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\PreEnquiry\PreEnquiry;
use App\Entity\RoRo\Survey as RoRoSurvey;
use App\Utility\AlphagovNotify\Reference;
use Doctrine\Common\Util\ClassUtils;

/**
 * Which Notify template each notification type uses, per survey class and channel. This is the only
 * place that knows which types apply to which survey class; a channel that is not sent (e.g. pre-enquiry
 * email, whose Reference ids are empty) is simply absent.
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
        ],
        InternationalSurvey::class => [
            'invite'          => [NotificationChannel::LETTER->value => Reference::LETTER_INTERNATIONAL_SURVEY_INVITE,     NotificationChannel::EMAIL->value => Reference::EMAIL_INTERNATIONAL_SURVEY_INVITE],
            'reminder-1'      => [NotificationChannel::LETTER->value => Reference::LETTER_INTERNATIONAL_SURVEY_REMINDER_1, NotificationChannel::EMAIL->value => Reference::EMAIL_INTERNATIONAL_SURVEY_REMINDER_1],
            'reminder-2'      => [NotificationChannel::LETTER->value => Reference::LETTER_INTERNATIONAL_SURVEY_REMINDER_2, NotificationChannel::EMAIL->value => Reference::EMAIL_INTERNATIONAL_SURVEY_REMINDER_2],
            'reminder-manual' => [NotificationChannel::EMAIL->value => Reference::EMAIL_INTERNATIONAL_SURVEY_MANUAL_REMINDER],
        ],
        // Letter only: Reference::EMAIL_PRE_ENQUIRY_* are empty strings, so no email is ever sent for pre-enquiries.
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

    /** @return array<string, string> channel value => template id; empty when the type does not apply to the class */
    public static function for(string $surveyClass, NotificationType $type): array
    {
        // Callers pass $survey::class; a survey fetched by a query can be a Doctrine proxy whose class is
        // Proxies\__CG__\...\Survey, so normalise to the mapped class once here rather than at every caller.
        return self::TEMPLATES[ClassUtils::getRealClass($surveyClass)][$type->value] ?? [];
    }

    public static function has(string $surveyClass, NotificationType $type): bool
    {
        return self::for($surveyClass, $type) !== [];
    }

    /** Types that apply to the class, in NotificationType::cases() order (the admin display order). @return list<NotificationType> */
    public static function typesFor(string $surveyClass): array
    {
        return array_values(array_filter(NotificationType::cases(), fn(NotificationType $t) => self::has($surveyClass, $t)));
    }
}
