<?php

declare(strict_types=1);

namespace App\Tests\Command\Dev;

use App\Command\Dev\LcniFixJammedNamesCommand;
use App\Entity\NotificationInterception;
use App\Entity\NotificationInterceptionCompanyName;
use Doctrine\ORM\EntityManagerInterface;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LcniFixJammedNamesCommandTest extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        static::bootKernel();

        $container = static::getContainer()->get('test.service_container');
        $container->get(DatabaseToolCollection::class)->get()->loadFixtures([]);

        $this->entityManager = $container->get(EntityManagerInterface::class);

        // sqlite doesn't enforce foreign keys by default; without this, tests can't catch
        // delete-ordering bugs that violate FK constraints on MySQL
        $this->entityManager->getConnection()->executeStatement('PRAGMA foreign_keys = ON');
    }

    protected function createInterception(array $names, ?string $csrgtEmails = 'fleet@example.com', ?string $dvlaEmails = null): NotificationInterception
    {
        $interception = (new NotificationInterception())
            ->setCsrgtEmails($csrgtEmails)
            ->setDvlaEmails($dvlaEmails);
        foreach ($names as $name) {
            $interception->addName($name);
        }
        $this->entityManager->persist($interception);
        $this->entityManager->flush();

        return $interception;
    }

    /**
     * Seed names exactly as given, bypassing setName()'s whitespace normalisation —
     * needed to reproduce historically-corrupted rows.
     */
    protected function createInterceptionWithRawNames(array $rawNames, ?string $csrgtEmails = 'fleet@example.com'): NotificationInterception
    {
        $placeholders = array_map(fn(int $i) => "__raw_{$i}__" . bin2hex(random_bytes(4)), array_keys($rawNames));
        $interception = $this->createInterception($placeholders, $csrgtEmails);

        $connection = $this->entityManager->getConnection();
        foreach ($placeholders as $i => $placeholder) {
            $connection->executeStatement(
                'UPDATE notification_interception_company_name SET name = ? WHERE name = ?',
                [$rawNames[$i], $placeholder],
            );
        }
        $this->entityManager->clear();

        return $interception;
    }

    protected function runCommand(array $options = []): CommandTester
    {
        $tester = new CommandTester(static::getContainer()->get(LcniFixJammedNamesCommand::class));
        $this->assertSame(0, $tester->execute($options));

        return $tester;
    }

    /**
     * @return array<string> the (sorted) alias names now attached to the interception
     */
    protected function getNames(NotificationInterception $interception): array
    {
        $names = $this->entityManager->createQueryBuilder()
            ->select('cn.name')
            ->from(NotificationInterceptionCompanyName::class, 'cn')
            ->where('cn.notificationInterception = :ni')
            ->setParameter('ni', $interception->getId())
            ->getQuery()
            ->getSingleColumnResult();
        sort($names);

        return $names;
    }

    public function testSplitsJammedRowIntoSeparateRows(): void
    {
        $interception = $this->createInterception(["Acme Ltd\nAcme Limited\nAcme"]);
        $jammedRowId = $interception->getNames()[0]->getId();

        $this->runCommand();

        $this->assertSame(['Acme', 'Acme Limited', 'Acme Ltd'], $this->getNames($interception));

        // The original row is retained, renamed to the first name in the jam
        $row = $this->entityManager->find(NotificationInterceptionCompanyName::class, $jammedRowId);
        $this->assertNotNull($row);
        $this->assertSame('Acme Ltd', $row->getName());
    }

    public function testSplitsCarriageReturnJams(): void
    {
        $interception = $this->createInterception(["Acme Ltd\r\nAcme Limited\rAcme"]);

        $this->runCommand();

        $this->assertSame(['Acme', 'Acme Limited', 'Acme Ltd'], $this->getNames($interception));
    }

    public function testDeduplicatesPartsCaseInsensitively(): void
    {
        $interception = $this->createInterception(["Acme Ltd\nACME LTD\nAcme"]);

        $this->runCommand();

        $this->assertSame(['Acme', 'Acme Ltd'], $this->getNames($interception));
    }

    public function testDropsPartsAlreadyPresentOnSameParent(): void
    {
        $interception = $this->createInterception(['Acme Ltd', "Acme Ltd\nAcme Limited"]);

        $this->runCommand();

        $this->assertSame(['Acme Limited', 'Acme Ltd'], $this->getNames($interception));
    }

    public function testRemovesJammedRowWhenAllPartsAlreadyOnParent(): void
    {
        $interception = $this->createInterception(['Acme Ltd', 'Acme Limited', "Acme Ltd\nAcme Limited"]);

        $this->runCommand();

        $this->assertSame(['Acme Limited', 'Acme Ltd'], $this->getNames($interception));
    }

    public function testConflictingPartIsSkippedAndReported(): void
    {
        $other = $this->createInterception(['Bloggs Ltd']);
        $interception = $this->createInterception(["Bloggs Ltd\nBloggs Limited"]);

        $tester = $this->runCommand();

        $this->assertSame(['Bloggs Limited'], $this->getNames($interception));
        $this->assertSame(['Bloggs Ltd'], $this->getNames($other));
        $this->assertStringContainsString('Bloggs Ltd', $tester->getDisplay());
        $this->assertStringContainsString('conflict', strtolower($tester->getDisplay()));
    }

    public function testMergeFlagMergesDvlaOnlyConflictingRecord(): void
    {
        $other = $this->createInterception(['Bloggs Ltd'], csrgtEmails: null, dvlaEmails: 'dvla@example.com');
        $interception = $this->createInterception(["Bloggs Ltd\nBloggs Limited"], dvlaEmails: 'existing-dvla@example.com');

        $tester = $this->runCommand(['--merge-dvla-duplicates' => true]);

        $this->assertSame(['Bloggs Limited', 'Bloggs Ltd'], $this->getNames($interception));

        $merged = $this->entityManager->find(NotificationInterception::class, $interception->getId());
        $this->assertSame('fleet@example.com', $merged->getCsrgtEmails());
        $this->assertSame('existing-dvla@example.com,dvla@example.com', $merged->getDvlaEmails());

        $this->assertNull($this->entityManager->find(NotificationInterception::class, $other->getId()));
        $this->assertStringContainsString('merged', strtolower($tester->getDisplay()));
    }

    public function testMergeFlagMovesAllAliasesOfMergedRecord(): void
    {
        $other = $this->createInterception(['Bloggs Ltd', 'Bloggs Haulage'], csrgtEmails: null, dvlaEmails: 'dvla@example.com');
        $interception = $this->createInterception(["Bloggs Ltd\nBloggs Limited"]);

        $this->runCommand(['--merge-dvla-duplicates' => true]);

        $this->assertSame(['Bloggs Haulage', 'Bloggs Limited', 'Bloggs Ltd'], $this->getNames($interception));
        $this->assertNull($this->entityManager->find(NotificationInterception::class, $other->getId()));
    }

    public function testMergeFlagRemovesJammedRowWhenAllPartsResolvedByMerge(): void
    {
        $other = $this->createInterception(['Bloggs Ltd', 'Bloggs Limited'], csrgtEmails: null, dvlaEmails: 'dvla@example.com');
        $interception = $this->createInterception(["Bloggs Ltd\nBloggs Limited"]);

        $this->runCommand(['--merge-dvla-duplicates' => true]);

        $this->assertSame(['Bloggs Limited', 'Bloggs Ltd'], $this->getNames($interception));
        $this->assertNull($this->entityManager->find(NotificationInterception::class, $other->getId()));
    }

    public function testMergeFlagDoesNotMergeRecordsWithManualEmails(): void
    {
        $other = $this->createInterception(['Bloggs Ltd'], csrgtEmails: 'manual@example.com');
        $interception = $this->createInterception(["Bloggs Ltd\nBloggs Limited"]);

        $tester = $this->runCommand(['--merge-dvla-duplicates' => true]);

        $this->assertSame(['Bloggs Limited'], $this->getNames($interception));
        $this->assertSame(['Bloggs Ltd'], $this->getNames($other));
        $this->assertNotNull($this->entityManager->find(NotificationInterception::class, $other->getId()));
        $this->assertStringContainsString('conflict', strtolower($tester->getDisplay()));
    }

    public function testConflictNotMergedWithoutFlag(): void
    {
        $other = $this->createInterception(['Bloggs Ltd'], csrgtEmails: null, dvlaEmails: 'dvla@example.com');
        $interception = $this->createInterception(["Bloggs Ltd\nBloggs Limited"]);

        $this->runCommand();

        $this->assertSame(['Bloggs Limited'], $this->getNames($interception));
        $this->assertNotNull($this->entityManager->find(NotificationInterception::class, $other->getId()));
    }

    public function testMergeFlagWithDryRunMakesNoChanges(): void
    {
        $other = $this->createInterception(['Bloggs Ltd'], csrgtEmails: null, dvlaEmails: 'dvla@example.com');
        $interception = $this->createInterception(["Bloggs Ltd\nBloggs Limited"]);

        $this->runCommand(['--merge-dvla-duplicates' => true, '--dry-run' => true]);

        $this->assertSame(["Bloggs Ltd\nBloggs Limited"], $this->getNames($interception));
        $this->assertNotNull($this->entityManager->find(NotificationInterception::class, $other->getId()));
    }

    public function testShowChangesReportsSplitDetail(): void
    {
        $interception = $this->createInterception(['Acme', "Acme Ltd\nACME\nAcme Limited"]);

        $tester = $this->runCommand(['--show-changes' => true]);
        $display = $tester->getDisplay();

        // The jammed value, with visible line breaks
        $this->assertStringContainsString('Acme Ltd\nACME\nAcme Limited', $display);
        $this->assertStringContainsString('renamed to "Acme Ltd"', $display);
        $this->assertStringContainsString('added "Acme Limited"', $display);
        // The sibling-duplicate part is reported as dropped
        $this->assertStringContainsString('dropped "ACME"', $display);
    }

    public function testShowChangesReportsRemovedRow(): void
    {
        $this->createInterception(['Acme Ltd', 'Acme Limited', "Acme Ltd\nAcme Limited"]);

        $tester = $this->runCommand(['--show-changes' => true]);

        $this->assertStringContainsString('removed', strtolower($tester->getDisplay()));
    }

    public function testSplitDetailNotShownWithoutFlag(): void
    {
        $this->createInterception(["Acme Ltd\nAcme Limited"]);

        $tester = $this->runCommand();

        $this->assertStringNotContainsString('renamed to', $tester->getDisplay());
    }

    public function testRowLeftUntouchedWhenAllPartsConflict(): void
    {
        $other = $this->createInterception(['Bloggs Ltd', 'Bloggs Limited']);
        $interception = $this->createInterception(["Bloggs Ltd\nBloggs Limited"]);

        $tester = $this->runCommand();

        $this->assertSame(["Bloggs Ltd\nBloggs Limited"], $this->getNames($interception));
        $this->assertSame(['Bloggs Limited', 'Bloggs Ltd'], $this->getNames($other));
        $this->assertStringContainsString('manual review', strtolower($tester->getDisplay()));
    }

    public function testDryRunMakesNoChanges(): void
    {
        $interception = $this->createInterception(["Acme Ltd\nAcme Limited"]);

        $tester = $this->runCommand(['--dry-run' => true]);

        $this->assertSame(["Acme Ltd\nAcme Limited"], $this->getNames($interception));
        $this->assertStringContainsString('dry run', strtolower($tester->getDisplay()));
    }

    public function testSecondRunFindsNothingToDo(): void
    {
        $this->createInterception(["Acme Ltd\nAcme Limited"]);

        $this->runCommand();
        $tester = $this->runCommand();

        $this->assertStringContainsString('no jammed', strtolower($tester->getDisplay()));
    }

    public function testCleanCommaNamesStripsTrailingComma(): void
    {
        $interception = $this->createInterception(['Acme Ltd,']);

        $this->runCommand(['--clean-comma-names' => true]);

        $this->assertSame(['Acme Ltd'], $this->getNames($interception));
    }

    public function testCleanCommaNamesStripsPostcode(): void
    {
        $interception = $this->createInterception(['Acme Ltd, WS119XG', 'Bloggs Haulage, S95AP']);

        $this->runCommand(['--clean-comma-names' => true]);

        $this->assertSame(['Acme Ltd', 'Bloggs Haulage'], $this->getNames($interception));
    }

    public function testCleanCommaNamesLeavesOtherCommaRowsAlone(): void
    {
        $interception = $this->createInterception([
            'Bloggs Ltd, Bloggs Limited',
            'BOURNEMOUTH, CHRISTCHURCH & POOLE COUNCIL',
        ]);

        $tester = $this->runCommand(['--clean-comma-names' => true]);

        $this->assertSame(
            ['BOURNEMOUTH, CHRISTCHURCH & POOLE COUNCIL', 'Bloggs Ltd, Bloggs Limited'],
            $this->getNames($interception)
        );
        // The summary line may be wrapped by the console, so normalise whitespace first
        $this->assertMatchesRegularExpression('/out\s+of\s+scope/i', $tester->getDisplay());
    }

    public function testCommaRowsUntouchedWithoutFlag(): void
    {
        $interception = $this->createInterception(['Acme Ltd,']);

        $this->runCommand();

        $this->assertSame(['Acme Ltd,'], $this->getNames($interception));
    }

    public function testCleanCommaNamesRemovesRowWhenCleanNameExistsOnSameParent(): void
    {
        $interception = $this->createInterception(['Acme Ltd', 'Acme Ltd,']);

        $this->runCommand(['--clean-comma-names' => true]);

        $this->assertSame(['Acme Ltd'], $this->getNames($interception));
    }

    public function testCleanCommaNamesDeletesRecordWhenCleanNameExistsElsewhere(): void
    {
        $other = $this->createInterception(['Acme Ltd']);
        $interception = $this->createInterception(['Acme Ltd,']);

        $tester = $this->runCommand(['--clean-comma-names' => true]);

        // The comma record was a dead duplicate of $other — deleted outright, emails and all
        $this->assertNull($this->entityManager->find(NotificationInterception::class, $interception->getId()));
        $this->assertSame(['Acme Ltd'], $this->getNames($other));
        $this->assertStringContainsString('deleted', strtolower($tester->getDisplay()));
    }

    public function testCleanCommaNamesDeletesOnlyRowWhenParentHasOtherAliases(): void
    {
        $other = $this->createInterception(['Acme Ltd']);
        $interception = $this->createInterception(['Acme Ltd,', 'Acme Haulage']);

        $this->runCommand(['--clean-comma-names' => true]);

        $this->assertSame(['Acme Haulage'], $this->getNames($interception));
        $this->assertNotNull($this->entityManager->find(NotificationInterception::class, $interception->getId()));
        $this->assertSame(['Acme Ltd'], $this->getNames($other));
    }

    public function testCleanCommaNamesWarnsWhenDeletedRecordHadDifferentEmails(): void
    {
        $this->createInterception(['Acme Ltd'], csrgtEmails: 'kept@example.com');
        $interception = $this->createInterception(['Acme Ltd,'], csrgtEmails: 'discarded@example.com');

        $tester = $this->runCommand(['--clean-comma-names' => true]);

        $this->assertNull($this->entityManager->find(NotificationInterception::class, $interception->getId()));
        $this->assertStringContainsString('discarded@example.com', $tester->getDisplay());
    }

    public function testCleanCommaNamesDoesNotWarnWhenDeletedRecordEmailsMatch(): void
    {
        $this->createInterception(['Acme Ltd'], csrgtEmails: 'same@example.com');
        $this->createInterception(['Acme Ltd,'], csrgtEmails: 'same@example.com');

        $tester = $this->runCommand(['--clean-comma-names' => true]);

        $this->assertStringNotContainsString('WARNING', $tester->getDisplay());
    }

    public function testCleanCommaNamesDryRunMakesNoChanges(): void
    {
        $interception = $this->createInterception(['Acme Ltd,']);

        $this->runCommand(['--clean-comma-names' => true, '--dry-run' => true]);

        $this->assertSame(['Acme Ltd,'], $this->getNames($interception));
    }

    public function testCleanDoubleSpacesCollapsesName(): void
    {
        $interception = $this->createInterceptionWithRawNames(['Acme  Ltd', "Bloggs\u{00A0}\u{00A0}Haulage"]);

        $this->runCommand(['--clean-double-spaces' => true]);

        $this->assertSame(['Acme Ltd', 'Bloggs Haulage'], $this->getNames($interception));
    }

    public function testCleanDoubleSpacesDeletesDuplicateRecord(): void
    {
        $other = $this->createInterception(['Acme Ltd']);
        $interception = $this->createInterceptionWithRawNames(['Acme  Ltd']);

        $tester = $this->runCommand(['--clean-double-spaces' => true]);

        $this->assertNull($this->entityManager->find(NotificationInterception::class, $interception->getId()));
        $this->assertSame(['Acme Ltd'], $this->getNames($other));
        $this->assertStringContainsString('deleted', strtolower($tester->getDisplay()));
    }

    public function testDoubleSpaceRowsUntouchedWithoutFlag(): void
    {
        $interception = $this->createInterceptionWithRawNames(['Acme  Ltd']);

        $this->runCommand();

        $this->assertSame(['Acme  Ltd'], $this->getNames($interception));
    }

    public function testCleanDoubleSpacesLeavesJammedRowsToJamPhase(): void
    {
        $interception = $this->createInterceptionWithRawNames(["Acme  Ltd\nAcme Limited"]);

        // Dry run: the jam phase doesn't persist its fix, so the row still contains a line
        // break when the double-space phase fetches — it must be left for the jam phase,
        // not have its newline collapsed into a space
        $tester = $this->runCommand(['--clean-double-spaces' => true, '--dry-run' => true]);

        $this->assertSame(["Acme  Ltd\nAcme Limited"], $this->getNames($interception));
        $this->assertStringNotContainsString('Acme Ltd Acme Limited', $tester->getDisplay());
    }

    public function testUnjammedRecordsAreUntouched(): void
    {
        $interception = $this->createInterception(['Acme Ltd', 'Acme Limited']);

        $this->runCommand();

        $this->assertSame(['Acme Limited', 'Acme Ltd'], $this->getNames($interception));
    }
}
