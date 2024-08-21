<?php

namespace App\Logger;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsMonologProcessor]
class UserProcessor
{
    public function __construct(
        protected TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $token = $this->tokenStorage->getToken();

        $record['context']['_ghost_meta']['user_identifier'] = $token?->getUser()?->getUserIdentifier();
        return $record;
    }
}
