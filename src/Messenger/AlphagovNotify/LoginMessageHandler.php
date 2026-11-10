<?php

namespace App\Messenger\AlphagovNotify;

use Alphagov\Notifications\Client;
use Alphagov\Notifications\Exception\ApiException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;

#[AsMessageHandler]
class LoginMessageHandler
{
    public function __construct(
        protected Client $alphagovNotify,
        // As the bundle's MessageHandler: identifies the environment in Notify callbacks (login emails have no
        // reference of their own). Null or '' (prod) means none.
        #[Autowire(env: 'default::APP_NOTIFY_REFERENCE_PREFIX')]
        protected ?string $referencePrefix = null,
    ) {}

    public function __invoke(RoRoLoginEmail $message): ?array
    {
        try {
            return $this->alphagovNotify->sendEmail(...[...$message->getSendMethodParameters(), $this->referencePrefix ?? '']);
        } catch (ApiException $apiException) {
            if (in_array($apiException->getCode(), [400, 403])) {
                throw new UnrecoverableMessageHandlingException('', 0, $apiException);
            }
            throw $apiException;
        }
    }
}
