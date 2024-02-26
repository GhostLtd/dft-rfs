<?php

namespace App\Messenger\AlphagovNotify;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\ApiException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

#[AsMessageHandler]
class LoginMessageHandler
{
    public function __construct(protected Client $alphagovNotify)
    {}

    public function __invoke(RoRoLoginEmail $message): ?array
    {
        try {
            return $this->alphagovNotify->sendEmail(...$message->getSendMethodParameters());
        } catch (ApiException $apiException) {
            if (in_array($apiException->getCode(), [400, 403])) {
                throw new UnrecoverableMessageHandlingException('', 0, $apiException);
            }
            throw $apiException;
        }
    }
}
