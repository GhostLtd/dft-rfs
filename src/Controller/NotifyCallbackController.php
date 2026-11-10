<?php

namespace App\Controller;

use App\Entity\NotifyCallback;
use App\Utility\AlphagovNotify\Callback\CallbackProcessor;
use App\Utility\AlphagovNotify\Callback\InvalidCallbackException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Receives GOV.UK Notify callbacks. Notify authenticates with the bearer token configured alongside the callback URL.
 *
 * Status codes matter: Notify sends a callback again after a network error, timeout (5s), 5xx or 429, but drops any
 * other 4xx. So 401/400 are only for requests that will never succeed; anything temporary must be a 5xx (unexpected
 * exceptions give a 500; the maintenance lock gives a 503, see MaintenanceLockSubscriber).
 */
class NotifyCallbackController extends AbstractController
{
    public const string ROUTE = 'app_notify_callback';

    public function __construct(
        private readonly CallbackProcessor $processor,
        private readonly LoggerInterface   $logger,
        #[Autowire(env: 'default::APP_NOTIFY_CALLBACK_TOKEN')]
        private readonly ?string           $callbackToken = null,
    ) {}

    #[Route(
        path: '/notify/callback/{type}',
        name: self::ROUTE,
        requirements: ['type' => NotifyCallback::TYPE_DELIVERY_RECEIPT.'|'.NotifyCallback::TYPE_RETURNED_LETTER],
        methods: ['POST'],
        stateless: true,
    )]
    public function callback(Request $request, string $type): Response
    {
        // Notify never sends a 4xx'd callback again, so each is logged (never the body: it holds the recipient)
        if (!$this->isAuthorised($request)) {
            $this->logger->warning('Notify callback refused: missing or wrong bearer token (or none configured)', ['type' => $type]);
            return new Response('', Response::HTTP_UNAUTHORIZED);
        }

        try {
            $payload = json_decode($request->getContent(), true, flags: JSON_THROW_ON_ERROR);
            $this->processor->process($type, $payload);
        } catch (\JsonException|InvalidCallbackException $e) {
            $this->logger->warning('Notify callback refused: invalid payload', ['type' => $type, 'reason' => $e->getMessage()]);
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        return new Response('', Response::HTTP_NO_CONTENT);
    }

    private function isAuthorised(Request $request): bool
    {
        // With no token configured, nothing is accepted
        if ($this->callbackToken === null || $this->callbackToken === '') {
            return false;
        }

        $header = $request->headers->get('Authorization', '');

        return str_starts_with($header, 'Bearer ')
            && hash_equals($this->callbackToken, substr($header, 7));
    }
}
