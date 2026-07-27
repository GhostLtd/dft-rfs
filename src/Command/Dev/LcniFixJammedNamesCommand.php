<?php

namespace App\Command\Dev;

use App\Command\MySqlGoneAwayRetryTrait;
use App\Entity\NotificationInterception;
use App\Entity\NotificationInterceptionCompanyName;
use App\Utility\EmailHelper;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// Root cause of this issue: https://github.com/symfony/symfony/issues/62611
#[AsCommand(
    name: 'rfs:dev:lcni-fix-jammed-names',
    description: 'Split LCNI company-name records that hold several newline-separated names (caused by symfony/form 6.4.42 newline normalisation) back into one record per name',
)]
class LcniFixJammedNamesCommand extends Command
{
    /**
     * Each jammed record is a self-contained unit of work (reload, split, flush), so the command
     * can be interrupted at any point and simply re-run: fixed records no longer contain newlines
     * and drop out of the work set. A retried unit whose flush actually succeeded is detected as
     * already fixed.
     */
    use MySqlGoneAwayRetryTrait;

    public function __construct(
        private readonly ManagerRegistry $registry,
        private EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Report what would be done without persisting any changes.')
            ->addOption('show-changes', null, InputOption::VALUE_NONE,
                'Show each change in detail: the jammed value, what the retained row is renamed to, and which names are added or dropped.')
            ->addOption('merge-dvla-duplicates', null, InputOption::VALUE_NONE,
                'When a name in a jam already exists on a DVLA-import-created record (dvla emails only), merge that '
                . 'record into the jammed one — move its names, merge its dvla emails, delete it — instead of skipping. '
                . 'Records with manually-entered csrgt/irhs emails are never merged.')
            ->addOption('clean-comma-names', null, InputOption::VALUE_NONE,
                'Also clean company names with a trailing comma ("Acme Ltd,") or a comma-appended postcode '
                . '("Acme Ltd, WS119XG"): renamed to the clean form, or deleted where the clean form already exists '
                . 'on another record (the damaged name can never match a lookup, so such rows are dead duplicates). '
                . 'Names with other commas (joined variants, legitimate commas) are left alone.')
            ->addOption('clean-double-spaces', null, InputOption::VALUE_NONE,
                'Also collapse runs of internal whitespace (double spaces, tabs, non-breaking spaces) in company '
                . 'names: renamed to the collapsed form, or deleted where the collapsed form already exists on '
                . 'another record. Such names can never match a lookup, which collapses whitespace on the input side.');
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');
        $mergeDvlaDuplicates = (bool) $input->getOption('merge-dvla-duplicates');
        $showChanges = (bool) $input->getOption('show-changes');

        $this->runJamPhase($io, $dryRun, $mergeDvlaDuplicates, $showChanges);

        if ($input->getOption('clean-comma-names')) {
            $this->runCleanupPhase(
                $io,
                $dryRun,
                $showChanges,
                'LCNI comma company-name cleanup',
                'other commas — manual review',
                fn(): array => $this->fetchCommaRecordIds(),
                fn(string $name): ?string => $this->cleanedCommaName($name),
            );
        }

        if ($input->getOption('clean-double-spaces')) {
            $this->runCleanupPhase(
                $io,
                $dryRun,
                $showChanges,
                'LCNI double-space company-name cleanup',
                'contain line breaks — handled by the jam fix',
                fn(): array => $this->fetchDoubleSpaceRecordIds(),
                fn(string $name): ?string => $this->collapsedDoubleSpaceName($name),
            );
        }

        return Command::SUCCESS;
    }

    protected function runJamPhase(SymfonyStyle $io, bool $dryRun, bool $mergeDvlaDuplicates, bool $showChanges): void
    {
        $io->section('LCNI jammed company-name fix' . ($dryRun ? ' (dry run)' : ''));

        $ids = $this->withRetry($io, 'loading jammed record IDs', fn(): array => $this->fetchJammedRecordIds());

        if (count($ids) === 0) {
            $io->success('No jammed company-name records found.');
            return;
        }

        $io->text(sprintf('Found %d jammed record(s).', count($ids)));

        $outcomes = [];
        $needingReview = [];
        $mergeCount = 0;

        foreach ($ids as $id) {
            $result = $this->withRetry($io, "fixing record {$id}", fn(): array => $this->fixRecord($id, $dryRun, $mergeDvlaDuplicates));
            $outcomes[$result['status']] = ($outcomes[$result['status']] ?? 0) + 1;

            $printedDetail = $showChanges && $result['detail'] !== null;
            if ($printedDetail) {
                $this->outputChangeDetail($io, $id, $result['status'], $result['detail']);
            }

            $this->reportMergesAndConflicts($io, $id, $result, $mergeCount, $printedDetail);

            if ($result['status'] === 'left') {
                $needingReview[] = $id;
                $io->warning(sprintf('Record %s: every name already exists on other records — left untouched.', $id));
            }
        }

        $io->success(sprintf(
            '%d split, %d removed as redundant, %d left for manual review, %d already fixed, %d DVLA duplicate(s) merged.%s',
            $outcomes['fixed'] ?? 0,
            $outcomes['removed'] ?? 0,
            $outcomes['left'] ?? 0,
            $outcomes['already-fixed'] ?? 0,
            $mergeCount,
            $dryRun ? ' (dry run — no changes persisted)' : '',
        ));

        if (count($needingReview) > 0) {
            $io->warning("Records needing manual review:\n" . implode("\n", $needingReview));
        }
    }

    /**
     * Shared rename-or-delete cleanup phase: for each fetched row, $cleaner returns the repaired
     * name (or null when out of scope). Rows are renamed when the clean form is unused, and
     * deleted as dead duplicates when it already exists — see {@see cleanRecordName()}.
     *
     * @param callable(): array<string> $fetchIds
     * @param callable(string): ?string $cleaner
     */
    protected function runCleanupPhase(
        SymfonyStyle $io,
        bool $dryRun,
        bool $showChanges,
        string $title,
        string $outOfScopeLabel,
        callable $fetchIds,
        callable $cleaner,
    ): void {
        $io->section($title . ($dryRun ? ' (dry run)' : ''));

        $ids = $this->withRetry($io, 'loading record IDs', fn(): array => $fetchIds());

        if (count($ids) === 0) {
            $io->success('No matching company-name records found.');
            return;
        }

        $io->text(sprintf('Found %d candidate record(s).', count($ids)));

        $outcomes = [];

        foreach ($ids as $id) {
            $result = $this->withRetry($io, "cleaning record {$id}", fn(): array => $this->cleanRecordName($id, $dryRun, $cleaner));
            $outcomes[$result['status']] = ($outcomes[$result['status']] ?? 0) + 1;

            $printedDetail = $showChanges && $result['detail'] !== null;
            if ($printedDetail) {
                $this->outputCleanupChangeDetail($io, $id, $result['status'], $result['detail']);
                $io->newLine();
            }

            if (count($result['discardedEmails']) > 0) {
                $io->warning(sprintf(
                    "Record %s: deleted record's emails were not all present on surviving LCNI %s — discarded: %s",
                    $id,
                    $result['detail']['survivorLcniId'],
                    implode(', ', $result['discardedEmails']),
                ));
            }
        }

        $io->success(sprintf(
            '%d renamed, %d duplicate rows removed, %d duplicate records deleted, %d out of scope (%s).%s',
            $outcomes['cleaned'] ?? 0,
            $outcomes['removed'] ?? 0,
            $outcomes['deleted-record'] ?? 0,
            $outcomes['out-of-scope'] ?? 0,
            $outOfScopeLabel,
            $dryRun ? ' (dry run — no changes persisted)' : '',
        ));
    }

    /**
     * @param array{status: string, conflicts: array<array{name: string, otherRowId: string, otherInterceptionId: string}>, merged: array<array{otherInterceptionId: string, names: array<string>, dvlaEmails: ?string}>} $result
     */
    protected function reportMergesAndConflicts(SymfonyStyle $io, string $id, array $result, int &$mergeCount, bool $printedDetail): void
    {
        foreach ($result['merged'] as $merged) {
            $mergeCount++;
            $io->text(sprintf(
                'Record <fg=cyan>%s</>: <fg=magenta>merged</> DVLA-created duplicate LCNI <fg=cyan>%s</> (names: <info>%s</info>; dvla emails: <comment>%s</comment>).',
                $id,
                $merged['otherInterceptionId'],
                OutputFormatter::escape(implode(', ', $merged['names'])),
                OutputFormatter::escape($merged['dvlaEmails'] ?: '(none)'),
            ));
        }

        // Blank line between record batches to keep each record's changes visually grouped
        if ($printedDetail || count($result['merged']) > 0) {
            $io->newLine();
        }

        foreach ($result['conflicts'] as $conflict) {
            $io->warning(sprintf(
                'Record %s: name "%s" conflicts with existing record %s (LCNI %s) — skipped.',
                $id,
                $conflict['name'],
                $conflict['otherRowId'],
                $conflict['otherInterceptionId'],
            ));
        }
    }

    /**
     * @return array<string> the IDs of company-name records containing a newline
     */
    protected function fetchJammedRecordIds(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('cn.id')
            ->from(NotificationInterceptionCompanyName::class, 'cn')
            ->where('cn.name LIKE :lf OR cn.name LIKE :cr')
            ->setParameter('lf', "%\n%")
            ->setParameter('cr', "%\r%")
            ->orderBy('cn.id', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * @return array<string> the IDs of company-name records containing a comma
     */
    protected function fetchCommaRecordIds(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('cn.id')
            ->from(NotificationInterceptionCompanyName::class, 'cn')
            ->where('cn.name LIKE :comma')
            ->setParameter('comma', '%,%')
            ->orderBy('cn.id', 'ASC')
            ->getQuery()
            ->getSingleColumnResult();
    }

    /**
     * @return array<string> the IDs of company-name records containing internal whitespace runs
     */
    protected function fetchDoubleSpaceRecordIds(): array
    {
        // The SQL side over-matches on MySQL — utf8mb4_unicode_ci treats NBSP as equal to a
        // regular space, making the :nbsp pattern match any name containing a space — so the
        // definitive check is done in PHP on the fetched names.
        $rows = $this->entityManager->createQueryBuilder()
            ->select('cn.id, cn.name')
            ->from(NotificationInterceptionCompanyName::class, 'cn')
            ->where('cn.name LIKE :doubleSpace OR cn.name LIKE :nbsp OR cn.name LIKE :tab')
            ->setParameter('doubleSpace', '%  %')
            ->setParameter('nbsp', "%\u{00A0}%")
            ->setParameter('tab', "%\t%")
            ->orderBy('cn.id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $rows = array_filter($rows, fn(array $row) => $this->collapsedDoubleSpaceName($row['name']) !== null);

        return array_column($rows, 'id');
    }

    /**
     * Collapse runs of horizontal whitespace (matching what setName() now enforces), or null when
     * nothing changes or the name is jammed (line breaks belong to the jam phase).
     */
    protected function collapsedDoubleSpaceName(string $name): ?string
    {
        if (preg_match('/\R/u', $name)) {
            return null;
        }

        $collapsed = $this->collapseSpaces($name);
        return $collapsed === $name ? null : $collapsed;
    }

    protected function collapseSpaces(string $name): string
    {
        return mb_trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $name));
    }

    /**
     * The cleaned form of a comma-damaged name, or null if the name is not one of the two
     * mechanical categories (trailing comma / comma-appended postcode) this command handles.
     */
    protected function cleanedCommaName(string $name): ?string
    {
        if (!str_contains($name, ',')) {
            return null;
        }

        $parts = array_map(fn(string $p) => mb_trim($p), explode(',', $name));
        $parts = array_values(array_filter($parts, fn(string $p) => $p !== ''));

        return match (true) {
            // "Acme Ltd,"
            count($parts) === 1 => $this->collapseSpaces($parts[0]),
            // "Acme Ltd, WS119XG"
            count($parts) === 2 && preg_match('/^[A-Za-z]{1,2}\d{1,2}[A-Za-z]?\s*\d[A-Za-z]{2}$/', $parts[1]) === 1 => $this->collapseSpaces($parts[0]),
            default => null,
        };
    }

    /**
     * Clean a single damaged record whose repaired name is produced by $cleaner. Self-contained
     * and idempotent for the same reasons as {@see fixRecord()}.
     *
     * A damaged name never matches a lookup, so where its clean form already exists — as a sibling
     * or on any other record — the damaged row is a dead duplicate and is deleted (along with its
     * parent LCNI record when it was the only name, since a name-less record can never match).
     * Only when the clean form is unused is the row renamed, restoring the intended interception.
     *
     * @param callable(string): ?string $cleaner
     * @return array{
     *     status: string,
     *     detail: ?array{originalName: string, cleanName: string, survivorLcniId: ?string},
     *     discardedEmails: array<string>,
     * }
     */
    protected function cleanRecordName(string $id, bool $dryRun, callable $cleaner): array
    {
        $this->entityManager->clear();

        $row = $this->entityManager->find(NotificationInterceptionCompanyName::class, $id);
        if (!$row) {
            return ['status' => 'already-fixed', 'detail' => null, 'discardedEmails' => []];
        }

        $originalName = $row->getName();
        $cleanName = $cleaner($originalName);
        if ($cleanName === null || $cleanName === $originalName) {
            return ['status' => 'out-of-scope', 'detail' => null, 'discardedEmails' => []];
        }

        $interception = $row->getNotificationInterception();
        $survivorLcniId = null;
        $discardedEmails = [];

        $existing = $this->findOtherRowWithName($cleanName, $id);
        if ($existing === null) {
            $row->setName($cleanName);
            $status = 'cleaned';
        } else {
            // The clean name is already in use — this damaged row is a dead duplicate.
            // Remove the row explicitly rather than relying on orphanRemoval: orphan removals
            // are only scheduled during flush, which would sequence the parent's DELETE first
            // and violate the FK constraint when the whole record is being deleted below.
            $survivorLcniId = $existing->getNotificationInterception()->getId();
            $interception->removeName($row);
            $this->entityManager->remove($row);
            if ($interception->getNames()->isEmpty()) {
                $discardedEmails = $this->emailsNotCoveredBy($interception, $existing->getNotificationInterception());
                $this->entityManager->remove($interception);
                $status = 'deleted-record';
            } else {
                $status = 'removed';
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return [
            'status' => $status,
            'detail' => ['originalName' => $originalName, 'cleanName' => $cleanName, 'survivorLcniId' => $survivorLcniId],
            'discardedEmails' => $discardedEmails,
        ];
    }

    /**
     * @return array<string> emails on $doomed that do not appear anywhere on $survivor
     */
    protected function emailsNotCoveredBy(NotificationInterception $doomed, NotificationInterception $survivor): array
    {
        $emailsOf = fn(NotificationInterception $ni): array => array_map('strtolower', array_merge(
            EmailHelper::convertStringListToArray($ni->getCsrgtEmails()),
            EmailHelper::convertStringListToArray($ni->getIrhsEmails()),
            EmailHelper::convertStringListToArray($ni->getDvlaEmails()),
        ));

        return array_values(array_diff(array_unique($emailsOf($doomed)), $emailsOf($survivor)));
    }

    /**
     * @param array{originalName: string, cleanName: string, survivorLcniId: ?string} $detail
     */
    protected function outputCleanupChangeDetail(SymfonyStyle $io, string $id, string $status, array $detail): void
    {
        $io->text(match ($status) {
            'cleaned' => sprintf(
                'Record <fg=cyan>%s</>: renamed "<comment>%s</comment>" → "<info>%s</info>"',
                $id,
                OutputFormatter::escape($detail['originalName']),
                OutputFormatter::escape($detail['cleanName']),
            ),
            'removed' => sprintf(
                'Record <fg=cyan>%s</>: <fg=yellow>removed row</> "<comment>%s</comment>" ("<info>%s</info>" already covered by LCNI <fg=cyan>%s</>)',
                $id,
                OutputFormatter::escape($detail['originalName']),
                OutputFormatter::escape($detail['cleanName']),
                $detail['survivorLcniId'],
            ),
            default => sprintf(
                'Record <fg=cyan>%s</>: <fg=yellow>deleted record</> "<comment>%s</comment>" (its only name; "<info>%s</info>" already covered by LCNI <fg=cyan>%s</>)',
                $id,
                OutputFormatter::escape($detail['originalName']),
                OutputFormatter::escape($detail['cleanName']),
                $detail['survivorLcniId'],
            ),
        });
    }

    /**
     * Fix a single jammed record. Self-contained (reloads from scratch) so it is safe to retry
     * after a reconnect: if a previous attempt's flush actually succeeded, the reloaded record no
     * longer contains newlines and is reported as already fixed.
     *
     * @return array{
     *     status: string,
     *     conflicts: array<array{name: string, otherRowId: string, otherInterceptionId: string}>,
     *     merged: array<array{otherInterceptionId: string, names: array<string>, dvlaEmails: ?string}>,
     *     detail: ?array{originalName: string, keptName: ?string, addedNames: array<string>, droppedNames: array<string>},
     * }
     */
    protected function fixRecord(string $id, bool $dryRun, bool $mergeDvlaDuplicates = false): array
    {
        $this->entityManager->clear();

        $row = $this->entityManager->find(NotificationInterceptionCompanyName::class, $id);
        if (!$row || !preg_match('/\R/u', $row->getName())) {
            return ['status' => 'already-fixed', 'conflicts' => [], 'merged' => [], 'detail' => null];
        }

        $interception = $row->getNotificationInterception();
        $originalName = $row->getName();

        $keep = [];
        $conflicts = [];
        $merged = [];
        $dropped = [];
        $allDuplicatesOfSiblings = true;
        foreach ($this->splitName($originalName) as $name) {
            $existing = $this->findOtherRowWithName($name, $id);
            $otherInterception = $existing?->getNotificationInterception();
            if ($existing === null) {
                $keep[] = $name;
                $allDuplicatesOfSiblings = false;
            } elseif ($otherInterception !== $interception) {
                if ($mergeDvlaDuplicates && $this->isDvlaOnly($otherInterception)) {
                    // The clashing record was created by the DVLA import because this (jammed) alias
                    // couldn't be matched; fold it back in. The name is then a sibling — drop it.
                    $merged[] = $this->mergeInterception($interception, $otherInterception);
                } else {
                    $conflicts[] = [
                        'name' => $name,
                        'otherRowId' => $existing->getId(),
                        'otherInterceptionId' => $otherInterception->getId(),
                    ];
                    $allDuplicatesOfSiblings = false;
                }
            } else {
                // This name already exists as a sibling on the same LCNI record — drop it
                $dropped[] = $name;
            }
        }

        if (count($keep) > 0) {
            $row->setName(array_shift($keep));
            foreach ($keep as $name) {
                $interception->addName($name);
            }
            $status = 'fixed';
        } elseif ($allDuplicatesOfSiblings) {
            // Every name in the jam already exists as a separate sibling row; the jammed row is redundant
            $interception->removeName($row);
            $status = 'removed';
        } else {
            // Nothing safe to write: every name exists, at least one on a different LCNI record
            return ['status' => 'left', 'conflicts' => $conflicts, 'merged' => $merged, 'detail' => null];
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        return [
            'status' => $status,
            'conflicts' => $conflicts,
            'merged' => $merged,
            'detail' => [
                'originalName' => $originalName,
                'keptName' => $status === 'fixed' ? $row->getName() : null,
                'addedNames' => $keep,
                'droppedNames' => $dropped,
            ],
        ];
    }

    /**
     * @param array{originalName: string, keptName: ?string, addedNames: array<string>, droppedNames: array<string>} $detail
     */
    protected function outputChangeDetail(SymfonyStyle $io, string $id, string $status, array $detail): void
    {
        $esc = fn(string $s): string => OutputFormatter::escape($s);

        $lines = [sprintf(
            'Record <fg=cyan>%s</>: split "<comment>%s</comment>"',
            $id,
            $esc(str_replace(["\r\n", "\r", "\n"], '\n', $detail['originalName'])),
        )];

        if ($detail['keptName'] !== null) {
            $lines[] = sprintf('  → existing row renamed to "<info>%s</info>"', $esc($detail['keptName']));
        }
        foreach ($detail['addedNames'] as $name) {
            $lines[] = sprintf('  → added "<info>%s</info>"', $esc($name));
        }
        foreach ($detail['droppedNames'] as $name) {
            $lines[] = sprintf('  <fg=gray>→ dropped "%s" (already present on this LCNI record)</>', $esc($name));
        }
        if ($status === 'removed') {
            $lines[] = '  <fg=yellow>→ removed row (every name already present)</>';
        }

        $io->text($lines);
    }

    protected function isDvlaOnly(NotificationInterception $interception): bool
    {
        return !$interception->getCsrgtEmails() && !$interception->getIrhsEmails();
    }

    /**
     * Fold a DVLA-import-created duplicate into $target: move its aliases across, merge its dvla
     * emails, then delete it. Persisted by the caller's flush (or discarded on dry-run).
     *
     * @return array{otherInterceptionId: string, names: array<string>, dvlaEmails: ?string}
     */
    protected function mergeInterception(NotificationInterception $target, NotificationInterception $duplicate): array
    {
        $movedNames = [];
        foreach ($duplicate->getNames()->toArray() as $companyName) {
            // Detach first: deleting $duplicate cascades to any names still in its collection.
            // addName() re-parents, unless the alias already exists on $target (case-insensitive),
            // in which case the detached row is deleted via orphanRemoval.
            $duplicate->removeName($companyName);
            $target->addName($companyName);
            $movedNames[] = $companyName->getName();
        }

        foreach (EmailHelper::convertStringListToArray($duplicate->getDvlaEmails()) as $email) {
            if (!$target->hasDvlaEmail($email)) {
                $target->addDvlaEmail($email);
            }
        }

        $this->entityManager->remove($duplicate);

        return [
            'otherInterceptionId' => $duplicate->getId(),
            'names' => $movedNames,
            'dvlaEmails' => $duplicate->getDvlaEmails(),
        ];
    }

    /**
     * @return array<string> trimmed, de-duplicated (case-insensitively, first spelling wins) names
     */
    protected function splitName(string $jammed): array
    {
        $parts = preg_split('/\R+/u', $jammed);
        // Collapse whitespace to match what setName() will store, so the existing-name
        // lookups below are checked against the form the name will actually take
        $parts = array_map(fn(string $p) => $this->collapseSpaces($p), $parts);
        $parts = array_filter($parts, fn(string $p) => $p !== '');

        $unique = [];
        foreach ($parts as $part) {
            $unique[mb_strtolower($part)] ??= $part;
        }

        return array_values($unique);
    }

    protected function findOtherRowWithName(string $name, string $excludeId): ?NotificationInterceptionCompanyName
    {
        return $this->entityManager->createQueryBuilder()
            ->select('cn')
            ->from(NotificationInterceptionCompanyName::class, 'cn')
            ->where('LOWER(cn.name) = LOWER(:name)')
            ->andWhere('cn.id != :id')
            ->setParameter('name', $name)
            ->setParameter('id', $excludeId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
