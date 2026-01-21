<?php

namespace App\Utility;

use App\Repository\EmailBlacklistRepository;
use Psr\Log\LoggerInterface;

class EmailBlacklistService
{
    public function __construct(
        private EmailBlacklistRepository $repository,
        private LoggerInterface $logger,
    ) {
    }

    public function isBlacklisted(string $email): bool
    {
        $result = $this->repository->isBlacklisted($email);

        if ($result) {
            $this->logger->info('Email is blacklisted: {email}', ['email' => $email]);
        }

        return $result;
    }

    public function filterBlacklistedEmails(array $emails): array
    {
        $filtered = $this->repository->filterBlacklistedEmails($emails);
        $removedCount = count($emails) - count($filtered);

        if ($removedCount > 0) {
            $this->logger->info('Filtered {count} blacklisted emails', ['count' => $removedCount]);
        }

        return $filtered;
    }

    public function getBlacklistedFromList(array $emails): array
    {
        return $this->repository->getBlacklistedFromList($emails);
    }
}
