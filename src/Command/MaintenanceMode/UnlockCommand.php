<?php

namespace App\Command\MaintenanceMode;

use App\Entity\Utility\MaintenanceLock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UnlockCommand extends Command
{
    protected static $defaultName = 'rfs:maintenance-mode:unlock';
    private SymfonyStyle $io;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Unlock the website for maintenance')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->unlock();
        $this->io->success('Maintenance mode is inactive.');

        return 0;
    }

    protected function unlock()
    {
        $this->entityManager->createQueryBuilder()
            ->delete()
            ->from(MaintenanceLock::class, 'm')
            ->getQuery()
            ->execute();
    }
}
