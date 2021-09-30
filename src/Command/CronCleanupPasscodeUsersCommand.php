<?php

namespace App\Command;

use App\Entity\AuditLog\AuditLog;
use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\International\Survey as InternationalSurvey;
use App\Entity\SurveyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronCleanupPasscodeUsersCommand extends Command
{
    protected static $defaultName = 'app:cron:cleanup:passcode-users';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Deletes passcode users for rejected surveys after six months')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $rejectedBefore = new \DateTime('6 months ago');

        $count = 0;

        $matchingDomesticSurveys = $this->entityManager->getRepository(DomesticSurvey::class)
            ->getSurveysRequiringPasscodeUserCleanup($rejectedBefore);

        $matchingInternationalSurveys = $this->entityManager->getRepository(InternationalSurvey::class)
            ->getSurveysRequiringPasscodeUserCleanup($rejectedBefore);

        $this->entityManager->beginTransaction();
        try {
            foreach ($matchingDomesticSurveys as $survey) {
                $this->entityManager->remove($survey->getPasscodeUser());
                $survey->setPasscodeUser(null);
                $this->entityManager->persist($this->getAuditLogEntry($survey));
                $count++;
            }

            foreach ($matchingInternationalSurveys as $survey) {
                $this->entityManager->remove($survey->getPasscodeUser());
                $survey->setPasscodeUser(null);
                $this->entityManager->persist($this->getAuditLogEntry($survey));
                $count++;
            }

            $this->entityManager->flush();
            $this->entityManager->commit();
        }
        catch(\Exception $e) {
            $this->entityManager->rollback();
            $io->error($e->getMessage());
            return 1;
        }


        $io->success(sprintf("Success - cleared %d passcode user(s)", $count));

        return 0;
    }

    protected function getAuditLogEntry(SurveyInterface $survey): AuditLog {
        return (new AuditLog())
            ->setCategory('cleanup-passcode')
            ->setUsername('-')
            ->setEntityId($survey->getId())
            ->setEntityClass(get_class($survey))
            ->setTimestamp(new \DateTime())
            ->setData([]);
    }
}
