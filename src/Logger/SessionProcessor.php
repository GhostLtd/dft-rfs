<?php

namespace App\Logger;

use Monolog\Attribute\AsMonologProcessor;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

#[AsMonologProcessor]
class SessionProcessor
{
    public function __construct(
        protected RequestStack $requestStack,
        protected TokenStorageInterface $tokenStorage,
    ) {}

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getMainRequest();

        if ($request) {
            $record['context']['_ghost_meta']['session'] = $request->cookies->get('PHPSESSID', null);
        }

        return $record;
    }
}
