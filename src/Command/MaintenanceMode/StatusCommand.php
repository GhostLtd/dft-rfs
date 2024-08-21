<?php

namespace App\Command\MaintenanceMode;

use App\Repository\MaintenanceLockRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('rfs:maintenance-mode:status')]
class StatusCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(private MaintenanceLockRepository $maintenanceLockRepository)
    {
        parent::__construct();
    }

    #[\Override]
    protected function configure(): void
    {
        $this
            ->setDescription('check the maintenance status')
        ;
    }

    #[\Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->io = new SymfonyStyle($input, $output);

        $this->status();

        return 0;
    }

    protected function status(): void
    {
        $whiteListedIPs = $this->maintenanceLockRepository->isLocked();
        if ($whiteListedIPs === false) {
            $this->io->success('Maintenance mode is NOT active.');
        } else {
            $this->io->success(sprintf('Maintenance mode is active. Whitelisted IPs: %s', implode(', ', $whiteListedIPs)));
        }
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
