<?php

namespace App\Twig;

use App\Utility\EmailBlacklistService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EmailBlacklistExtension extends AbstractExtension
{
    public function __construct(
        private EmailBlacklistService $emailBlacklistService,
    ) {
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('get_blacklisted_emails', $this->getBlacklistedEmails(...)),
            new TwigFunction('is_email_blacklisted', $this->isEmailBlacklisted(...)),
        ];
    }

    /**
     * @param string[] $emails
     * @return string[]
     */
    public function getBlacklistedEmails(array $emails): array
    {
        return $this->emailBlacklistService->getBlacklistedFromList($emails);
    }

    public function isEmailBlacklisted(string $email): bool
    {
        return $this->emailBlacklistService->isBlacklisted($email);
    }
}
