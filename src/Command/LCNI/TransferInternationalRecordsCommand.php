<?php

declare(strict_types=1);

namespace App\Command\LCNI;

use App\Entity\Domestic\NotificationInterception as CsrgtNotificationInterception;
use App\Entity\International\NotificationInterception as IrhsNotificationInterception;
use App\Entity\NotificationInterception;
use App\Repository\NotificationInterceptionRepository;
use App\Utility\EmailHelper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:lcni:transfer', description: 'Transfer IRHS and CSRGT LCNI records to common set')]
class TransferInternationalRecordsCommand extends Command
{
    private const bool MATCH_BY_EMAILS = false;
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly NotificationInterceptionRepository $repo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->importIrhsRecords($output);
        $this->importCsrgtRecords($output);

        return Command::SUCCESS;
    }

    protected function importCsrgtRecords(OutputInterface $output): void
    {
        $csrgtRepo = $this->entityManager->getRepository(CsrgtNotificationInterception::class);
        $csrgtRecords = $csrgtRepo->findAll();
        $added = $merged = 0;
        $csrgtCount = count($csrgtRecords);
        $output->writeln("Total CSRGT records: {$csrgtCount}");

        $tempExistingRecords = $this->repo->findAll();
        $existingRecordsByName = [];
        foreach ($tempExistingRecords as $record) {
            foreach ($record->getNames() as $nameObject) {
                $existingRecordsByName[strtolower($nameObject->getName())] = $record;
            }
        }

        $newRecordsByCsrgtEmails = [];
        foreach ($csrgtRecords as $csrgtRecord) {
            // find any existing record by name
            $existing = $existingRecordsByName[strtolower($csrgtRecord->getPrimaryName())] ?? null;
            if ($existing) {
                $existing->setCsrgtEmails(strtolower($csrgtRecord->getEmails()));
                $merged++;
                $newRecordsByCsrgtEmails[$existing->getCsrgtEmails()] = $existing;
            } else {
                // find an existing record by email
                $existing = $newRecordsByCsrgtEmails[strtolower($csrgtRecord->getEmails())] ?? null;
                if (self::MATCH_BY_EMAILS && $existing) {
                    $existing->addName($csrgtRecord->getPrimaryName());
                    $merged++;
                } else {
                    $record = (new NotificationInterception())
                        ->addName($csrgtRecord->getAddressLine())
                        ->setCsrgtEmails(EmailHelper::mergeStringLists(strtolower($csrgtRecord->getEmails())))
                    ;
                    $this->entityManager->persist($record);
                    $added++;
                    $newRecordsByCsrgtEmails[$record->getCsrgtEmails()] = $record;
                }
            }
            $x = $added + $merged;
            if ($x / 100.0 === ceil($x / 100.0)) {
                $this->entityManager->flush();
                $output->write("Added: {$added} Merged: {$merged}\r");
            }
        }
        $this->entityManager->flush();
        $output->writeln("Added: {$added} Merged: {$merged}");
    }

    protected function importIrhsRecords(OutputInterface $output): void
    {
        $irhsRepo = $this->entityManager->getRepository(IrhsNotificationInterception::class);

        $irhsRecords = $irhsRepo->createQueryBuilder('ni')
            ->select('ni, an')
            ->leftJoin('ni.additionalNames', 'an')
            ->getQuery()
            ->getResult();

        $added = $merged = 0;
        $irhsCount = count($irhsRecords);
        $output->writeln("Total IRHS records: {$irhsCount}");

        $newRecords = [];
        foreach ($irhsRecords as $irhsRecord) {
            // search for the email address in common records (to join multiple records together)
            $existing = $newRecords[strtolower($irhsRecord->getEmails())] ?? null;
            if (self::MATCH_BY_EMAILS && $existing) {
                foreach ($irhsRecord->getAllNames() as $name) {
                    $existing->addName($name);
                }
                $merged++;
            } else {
                $record = (new NotificationInterception())
                    ->addName($irhsRecord->getPrimaryName())
                    ->setIrhsEmails(EmailHelper::mergeStringLists(strtolower($irhsRecord->getEmails())))
                ;
                foreach ($irhsRecord->getAdditionalNames() as $nameObject) {
                    $record->addName($nameObject->getName());
                }
                $this->entityManager->persist($record);
                $added++;
                $newRecords[$record->getIrhsEmails()] = $record;
            }
            $x = $added + $merged;
            if ($x / 100.0 === ceil($x / 100.0)) {
                $this->entityManager->flush();
                $output->write("Added: {$added} Merged: {$merged}\r");
            }
        }
        $this->entityManager->flush();
        $output->writeln("Added: {$added} Merged: {$merged}");
    }
}
