<?php

namespace App\Utility\Rehearsal;

use App\Entity\NotifyApiResponse;
use App\Entity\RoRoUser;
use App\Entity\SurveyNotificationInterface;
use App\Entity\SurveyNotificationsInterface;
use App\EventSubscriber\AlphagovNotifyMessengerSubscriber;
use App\Messenger\AlphagovNotify\ApiResponseInterface;
use App\Utility\Notification\NotificationType;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Compares run-created notifications with each scenario's expected types, channels and latest Notify status.
 * Ignores seeded history. Missing or duplicate sends fail; invitations still inside their delay can wait.
 */
class SeedReporter
{
    private const string INVITE_DELAY = 'PT4H';

    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * Returns an instruction to recreate the database and seed again when the manifest is more than 24 hours old; otherwise null.
     */
    public function getRefusalReason(SeedManifest $manifest, \DateTimeImmutable $now): ?string
    {
        return $manifest->isStale($now)
            ? sprintf('The seed is from %s, over 24 hours ago; several scenarios are no longer valid. Recreate the scratch database and seed again (docs/RemindersRehearsal.md, steps A2–A3).', $manifest->seededAt->format('Y-m-d H:i'))
            : null;
    }

    /**
     * Returns a description of settings changed since seeding, or null when unchanged.
     *
     * @param array{from: string, disabled: list<string>, smsCap: int} $seeded
     * @param array{from: string, disabled: list<string>, smsCap: int} $current
     */
    public function settingsWarning(array $seeded, array $current): ?string
    {
        $changed = [];
        if ($seeded['from'] !== $current['from']) {
            $changed[] = "APP_IN_PROGRESS_REMINDERS_FROM {$seeded['from']} → {$current['from']}";
        }
        if ($seeded['disabled'] != $current['disabled']) {
            $changed[] = 'APP_DISABLE_REMINDERS ['.implode(',', $seeded['disabled']).'] → ['.implode(',', $current['disabled']).']';
        }
        if ($seeded['smsCap'] !== $current['smsCap']) {
            $changed[] = "APP_SMS_ANNUAL_CAP {$seeded['smsCap']} → {$current['smsCap']}";
        }
        return $changed === [] ? null : 'Settings changed since seeding, so some scenarios may no longer match: '.implode('; ', $changed);
    }

    /**
     * Returns one PASS, FAIL or WAIT result per scenario, with observed notifications and explanatory detail.
     *
     * @return list<ScenarioOutcome>
     */
    public function outcomes(SeedManifest $manifest, \DateTimeImmutable $now): array
    {
        $history = array_flip($manifest->historyRowIds);
        $invitesDue = $manifest->seededAt->add(new \DateInterval(self::INVITE_DELAY));
        $outcomes = [];

        foreach (ScenarioCatalogue::all() as $scenario) {
            $entry = $manifest->scenarios[$scenario->name];
            $survey = $this->entityManager->find($entry['class'], $entry['id']);
            if (!$survey instanceof SurveyNotificationsInterface) {
                $outcomes[] = new ScenarioOutcome($scenario, $entry['id'], [], ScenarioOutcome::FAIL, 'survey not found');
                continue;
            }

            /** @var array<string, list<string>> $rows row key => the status of each run-created row with that key */
            $rows = [];
            foreach ($survey->getNotifications() as $row) {
                if (isset($history[(string) $row->getId()])) {
                    continue; // seeded history, not the run's work
                }
                $key = (new ExpectedRow($row->getType(), $row->getChannel()))->key();
                $rows[$key][] = $scenario->family === Scenario::RORO ? $this->latestRoRoStatus($manifest, $row->getType()) : $this->latestNotificationStatus($row);
            }
            ksort($rows);
            $found = array_map(fn(array $statuses) => implode('+', $statuses), $rows);
            $duplicates = array_keys(array_filter($rows, fn(array $statuses) => count($statuses) > 1));

            $expected = array_map(fn(ExpectedRow $e) => $e->key(), $scenario->expect);
            sort($expected);

            if ($duplicates !== []) {
                $verdict = ScenarioOutcome::FAIL;
                $detail = 'sent more than once: '.implode(', ', array_map(fn($k) => "{$k} ×".count($rows[$k]), $duplicates));
            } elseif (array_keys($found) !== $expected) {
                $verdict = ScenarioOutcome::FAIL;
                $detail = 'expected ['.implode(', ', $expected).'], found ['.implode(', ', array_keys($found)).']';
            } elseif (array_filter($found, fn(string $s) => $s !== 'accepted') === []) {
                $verdict = ScenarioOutcome::PASS;
                $detail = $expected === [] ? 'nothing sent, as expected' : 'all accepted';
            } elseif ($scenario->invite && $now < $invitesDue && array_filter($found, fn(string $s) => $s !== 'pending') === []) {
                $verdict = ScenarioOutcome::WAIT;
                $detail = 'invites are delayed 4 hours: run messenger:consume and --report again after '.$invitesDue->format('H:i');
            } else {
                $verdict = ScenarioOutcome::FAIL;
                $detail = 'statuses: '.implode(', ', array_map(fn($k, $s) => "{$k} {$s}", array_keys($found), $found));
                if (in_array('pending', $found, true)) {
                    $detail .= ' — if these are just slow to send, run messenger:consume (runbook step A6) and try --report again';
                }
            }

            $outcomes[] = new ScenarioOutcome($scenario, $entry['id'], $found, $verdict, $detail);
        }

        return $outcomes;
    }

    /**
     * Returns the latest Notify status for a notification, or "pending" when no response exists.
     */
    private function latestNotificationStatus(SurveyNotificationInterface $row): string
    {
        if (!$row instanceof ApiResponseInterface) {
            return 'pending'; // only RoRo rows lack Notify responses, and those go through latestRoRoStatus() instead
        }
        $responses = $row->getNotifyApiResponses()->toArray();
        if ($responses === []) {
            return 'pending';
        }
        usort($responses, fn(NotifyApiResponse $a, NotifyApiResponse $b) => $b->getTimestamp() <=> $a->getTimestamp());
        return (string) ($responses[0]->getData()[AlphagovNotifyMessengerSubscriber::STATUS_KEY] ?? 'unknown');
    }

    /**
     * Returns the latest Notify status for this reminder type on the seeded RoRo user, or "pending".
     */
    private function latestRoRoStatus(SeedManifest $manifest, NotificationType $type): string
    {
        $user = $this->entityManager->find(RoRoUser::class, $manifest->roroUserId);
        $responses = array_values(array_filter($user?->getNotifyApiResponses()->toArray() ?? [], fn(NotifyApiResponse $r) => $r->getEventName() === $type->value));
        if ($responses === []) {
            return 'pending';
        }
        usort($responses, fn(NotifyApiResponse $a, NotifyApiResponse $b) => $b->getTimestamp() <=> $a->getTimestamp());
        return (string) ($responses[0]->getData()[AlphagovNotifyMessengerSubscriber::STATUS_KEY] ?? 'unknown');
    }
}
