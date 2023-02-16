<?php

namespace App\Security;

class ManagementUserHelper
{
    protected array $managementDomains;

    public function __construct(?array $managementDomains = null)
    {
        $this->managementDomains = $managementDomains ?? [];
    }

    public function isManagementDomain(string $email): bool
    {
        if (empty($this->managementDomains) || !str_contains($email, '@')) {
            return false;
        }

        $emailParts = explode('@', $email);

        if (count($emailParts) !== 2) {
            return false;
        }

        $domain = strtolower($emailParts[1]);

        foreach($this->managementDomains as $managementDomain) {
            if ($domain === strtolower($managementDomain)) {
                return true;
            }
        }

        return false;
    }
}