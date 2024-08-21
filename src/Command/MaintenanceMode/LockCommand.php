<?php

namespace App\Command\MaintenanceMode;

use App\Entity\Utility\MaintenanceLock;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('rfs:maintenance-mode:lock')]
class LockCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('Lock the website for maintenance')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'required - molly guard')
            ->addOption('whitelist-ip', 'w', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'comma separated list of IPs to whitelist')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);
        $whitelistIps = $input->getOption('whitelist-ip');
        if (!$input->getOption('force')) {
            $this->io->error('The --force option is required');
            return 1;
        }

        if (!$this->validateIps($whitelistIps)) {
            return 1;
        }

        $this->lock($whitelistIps);
        $this->io->success(sprintf('Maintenance mode is active. Whitelisted IPs: %s', implode(', ', $whitelistIps)));

        return 0;
    }

    protected function lock(array $whitelistIps): void
    {
        $this->entityManager->beginTransaction();
        $this->entityManager->createQueryBuilder()
            ->delete()
            ->from(MaintenanceLock::class, 'm')
            ->getQuery()
            ->execute();
        $lock = new MaintenanceLock();
        $lock->setWhitelistedIps($whitelistIps);
        $this->entityManager->persist($lock);
        $this->entityManager->flush();
        $this->entityManager->commit();
    }

    protected function validateIps(array $ips): bool
    {
        $result = array_diff($ips, filter_var_array($ips, FILTER_VALIDATE_IP));
        if (empty($result)) {
            return true;
        }
        $this->io->error(sprintf('You specified invalid whitelist IP(s): %s', implode(', ', $result)));
        return false;
    }
}
