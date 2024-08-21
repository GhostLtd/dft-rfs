<?php

namespace App\Messenger\AlphagovNotify;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\ApiException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class NotificationMessageHandler
{
    public function __construct(protected Client $alphagovNotify)
    {}

    public function __invoke(AbstractSendMessage $message): ?array
    {
        try {
            return match ($message::class) {
                Email::class => $this->alphagovNotify->sendEmail(...$message->getSendMethodParameters()),
                Sms::class => $this->alphagovNotify->sendSms(...$message->getSendMethodParameters()),
                Letter::class => $this->alphagovNotify->sendLetter(...$message->getSendMethodParameters()),
                default => throw new \UnexpectedValueException($message::class),
            };
        } catch (ApiException $apiException) {
            if (in_array($apiException->getCode(), [400, 403])) {
                throw new UnrecoverableMessageHandlingException('', 0, $apiException);
            }
            throw $apiException;
        }
    }
}
