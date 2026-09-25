<?php

namespace App\Utility\Rehearsal;

use App\Entity\SurveyStateInterface;
use App\Utility\Notification\NotificationChannel as C;
use App\Utility\Notification\NotificationType as T;

/**
 * Defines rehearsal scenarios and expected deliveries, with dates relative to seeding.
 */
final class ScenarioCatalogue
{
    /**
     * Returns every rehearsal scenario in display and seeding order.
     *
     * @return list<Scenario>
     */
    public static function all(): array
    {
        $invited = SurveyStateInterface::STATE_INVITATION_SENT;
        $inProgress = SurveyStateInterface::STATE_IN_PROGRESS;
        $r = fn(T $t, C ...$channels) => array_map(fn(C $c) => new ExpectedRow($t, $c), $channels);

        $notStarted = function (string $family, string $prefix, array $marks) use ($invited, $r): array {
            return [
                new Scenario("{$prefix}:r1-due", $family, $invited, $r(T::REMINDER_1, C::LETTER, C::EMAIL), 'reminder 1: letter + email',
                    periodEndDays: -10, history: [[T::INVITE, 10]], address: true, invitationEmail: true, registrationMark: $marks[0], reference: $marks[0] === null ? "SEED-{$prefix}-01" : null),
                new Scenario("{$prefix}:r2-due", $family, $invited, $r(T::REMINDER_2, C::LETTER, C::EMAIL), 'reminder 2: letter + email',
                    periodEndDays: -30, history: [[T::INVITE, 30], [T::REMINDER_1, 16]], address: true, invitationEmail: true, registrationMark: $marks[1], reference: $marks[1] === null ? "SEED-{$prefix}-02" : null),
                new Scenario("{$prefix}:r1-email-only", $family, $invited, $r(T::REMINDER_1, C::EMAIL), 'reminder 1: email only',
                    periodEndDays: -10, history: [[T::INVITE, 10]], invitationEmail: true, registrationMark: $marks[2], reference: $marks[2] === null ? "SEED-{$prefix}-03" : null),
                new Scenario("{$prefix}:r1-period-not-ended", $family, $invited, [], 'nothing',
                    periodEndDays: 5, history: [[T::INVITE, 10]], address: true, invitationEmail: true, registrationMark: $marks[3], reference: $marks[3] === null ? "SEED-{$prefix}-04" : null),
            ];
        };

        return [
            // Invites: persisted in state "new", so the lifecycle listener's invite_user queues a real 4h-delayed invite.
            new Scenario('csrgt:invite', Scenario::CSRGT, null, $r(T::INVITE, C::LETTER, C::EMAIL), 'invite: letter + email (after 4h)',
                invite: true, periodEndDays: 14, address: true, invitationEmail: true, registrationMark: 'SD00NAA'),
            new Scenario('irhs:invite', Scenario::IRHS, null, $r(T::INVITE, C::LETTER, C::EMAIL), 'invite: letter + email (after 4h)',
                invite: true, periodEndDays: 14, address: true, invitationEmail: true, reference: 'SEED-irhs-00'),
            new Scenario('pre:invite', Scenario::PRE, null, $r(T::INVITE, C::LETTER), 'invite: letter (after 4h)',
                invite: true, address: true, reference: 'SEED-pre-00'),

            ...$notStarted(Scenario::CSRGT, 'csrgt', ['SD00NAB', 'SD00NAC', 'SD00NAD', 'SD00NAE']),
            ...$notStarted(Scenario::IRHS, 'irhs', [null, null, null, null]),

            new Scenario('pre:r1-due', Scenario::PRE, $invited, $r(T::REMINDER_1, C::LETTER), 'reminder 1: letter',
                history: [[T::INVITE, 16]], address: true, reference: 'SEED-pre-01'),
            new Scenario('pre:r2-due', Scenario::PRE, $invited, $r(T::REMINDER_2, C::LETTER), 'reminder 2: letter',
                history: [[T::INVITE, 30], [T::REMINDER_1, 16]], address: true, reference: 'SEED-pre-02'),

            new Scenario('roro:r1-due', Scenario::RORO, SurveyStateInterface::STATE_NEW, $r(T::REMINDER_1, C::EMAIL), 'reminder 1: digest email (route A)'),
            new Scenario('roro:r2-due', Scenario::RORO, SurveyStateInterface::STATE_NEW, $r(T::REMINDER_2, C::EMAIL), 'reminder 2: digest email (route B)',
                history: [[T::REMINDER_1, 8]]),

            new Scenario('ip:r1-on-from', Scenario::IN_PROGRESS, $inProgress, $r(T::IN_PROGRESS_REMINDER_1, C::EMAIL, C::SMS), 'in-progress reminder 1: email + SMS',
                periodEndAtFrom: 0, contactEmail: true, telephone: '07700 900100', registrationMark: 'SD00PAA'),
            new Scenario('ip:r1-sms', Scenario::IN_PROGRESS, $inProgress, $r(T::IN_PROGRESS_REMINDER_1, C::EMAIL, C::SMS), 'in-progress reminder 1: email + SMS',
                periodEndDays: -10, contactEmail: true, telephone: '07700 900101', registrationMark: 'SD00PAB'),
            new Scenario('ip:r1-landline', Scenario::IN_PROGRESS, $inProgress, $r(T::IN_PROGRESS_REMINDER_1, C::EMAIL), 'in-progress reminder 1: email only (landline)',
                periodEndDays: -10, contactEmail: true, telephone: '01632 960000', registrationMark: 'SD00PAC'),
            new Scenario('ip:r1-no-contact', Scenario::IN_PROGRESS, $inProgress, [], 'nothing (no contact details); stays due',
                periodEndDays: -10, registrationMark: 'SD00PAD'),
            new Scenario('ip:r2-due', Scenario::IN_PROGRESS, $inProgress, $r(T::IN_PROGRESS_REMINDER_2, C::EMAIL, C::SMS), 'in-progress reminder 2: email + SMS',
                periodEndDays: -30, history: [[T::IN_PROGRESS_REMINDER_1, 15]], contactEmail: true, telephone: '07700 900102', registrationMark: 'SD00PAE'),
            new Scenario('ip:r3-due', Scenario::IN_PROGRESS, $inProgress, $r(T::IN_PROGRESS_REMINDER_3, C::EMAIL, C::SMS), 'in-progress reminder 3: email + SMS',
                periodEndDays: -45, history: [[T::IN_PROGRESS_REMINDER_1, 30], [T::IN_PROGRESS_REMINDER_2, 15]], contactEmail: true, telephone: '07700 900103', registrationMark: 'SD00PAF'),
            new Scenario('ip:chased-recently', Scenario::IN_PROGRESS, $inProgress, [], 'nothing (chased 2 days ago; held back)',
                periodEndDays: -10, contactEmail: true, chasedDaysAgo: 2, registrationMark: 'SD00PAG'),
            new Scenario('ip:manual-recently', Scenario::IN_PROGRESS, $inProgress, [], 'nothing (manual reminder 2 days ago; held back)',
                periodEndDays: -10, history: [[T::MANUAL_REMINDER, 2]], contactEmail: true, registrationMark: 'SD00PAH'),
            new Scenario('ip:closed', Scenario::IN_PROGRESS, SurveyStateInterface::STATE_CLOSED, [], 'nothing (closed)',
                periodEndDays: -10, contactEmail: true, registrationMark: 'SD00PAJ'),
            new Scenario('ip:before-from', Scenario::IN_PROGRESS, $inProgress, [], 'nothing (period ended before APP_IN_PROGRESS_REMINDERS_FROM)',
                periodEndAtFrom: -1, contactEmail: true, registrationMark: 'SD00PAK'),
        ];
    }

    /**
     * Returns the named scenario, or throws InvalidArgumentException when it is unknown.
     */
    public static function byName(string $name): Scenario
    {
        foreach (self::all() as $scenario) {
            if ($scenario->name === $name) {
                return $scenario;
            }
        }
        throw new \InvalidArgumentException("No scenario {$name}");
    }
}
