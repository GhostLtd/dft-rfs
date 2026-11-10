<?php

namespace App\Messenger;

use App\Utility\Notification\SmsAllowance;
use App\Utility\Notification\SmsRecipientDailyCap;
use Ghost\GovUkCoreBundle\Messenger\AlphagovNotify\Sms;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\UnrecoverableMessageHandlingException;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

/**
 * Checks both SMS caps before a received message reaches Notify.
 * Prepaid first attempts are free on their reserved day/year; retries consume new units.
 * A zero or invalid cap stops every attempt. Refusals fail terminally and are logged as failed Notify responses.
 */
class SmsAllowanceMiddleware implements MiddlewareInterface
{
    private const string LOG_PREFIX = 'Notifications: ';

    public function __construct(
        private readonly SmsAllowance $smsAllowance,
        private readonly SmsRecipientDailyCap $dailyCap,
        private readonly ClockInterface $clock,
        private readonly LoggerInterface $logger,
    ) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $message = $envelope->getMessage();
        if ($message instanceof Sms && $envelope->last(ReceivedStamp::class) !== null) {
            $this->chargeAttempt($message, $this->clock->now());
        }

        return $stack->next()->handle($envelope, $stack);
    }

    /**
     * Records an SMS attempt and charges any daily or annual unit not prepaid for it.
     * Throws an unrecoverable exception if either cap refuses the attempt.
     */
    private function chargeAttempt(Sms $message, \DateTimeImmutable $now): void
    {
        $notificationId = $message->getOriginatingEntityId();
        if ($notificationId === null) {
            throw new UnrecoverableMessageHandlingException('SMS has no originating notification row', 0, new \LogicException('SMS has no originating notification row'));
        }
        $mobile = $message->getPhoneNumber();
        $reference = $message->getReference();

        // Stop before recording an attempt: invalid values log an error; configured zero logs a warning.
        if ($this->smsAllowance->effectiveCap() === 0) {
            if ($this->smsAllowance->isConfigValid()) {
                $this->logger->warning(self::LOG_PREFIX."SMS annual cap is 0; refused attempt for {$reference}");
            } else {
                $this->smsAllowance->tryConsume($now);
            }
            throw $this->annualCapRefusal($now);
        }
        if ($this->dailyCap->effectiveCap() === 0) {
            if ($this->dailyCap->isConfigValid()) {
                $this->logger->warning(self::LOG_PREFIX."SMS per-number cap is 0; refused attempt for {$reference}");
            } else {
                $this->dailyCap->tryConsume($mobile, $now);
            }
            throw new SmsCapRefusedException('SMS per-number daily cap reached');
        }

        $record = $this->smsAllowance->recordAttempt($notificationId, $now);
        $firstAttempt = !$record->wasMissing && $record->attempts === 1;

        // Charge daily first so a later refusal does not waste an annual unit.
        $paidToday = $firstAttempt && $record->reservedOn === SmsRecipientDailyCap::day($now);
        if (!$paidToday && !$this->dailyCap->tryConsume($mobile, $now)) {
            $this->logger->warning(self::LOG_PREFIX."SMS per-number daily cap reached; refused attempt for {$reference}");
            throw new SmsCapRefusedException('SMS per-number daily cap reached');
        }

        $paidThisYear = $firstAttempt && $record->financialYear === $this->smsAllowance->financialYear($now);
        if (!$paidThisYear && !$this->smsAllowance->tryConsume($now)) {
            $this->logger->warning(self::LOG_PREFIX."SMS allowance exhausted for FY {$this->smsAllowance->financialYear($now)}; refused attempt for {$reference}");
            throw $this->annualCapRefusal($now);
        }
    }

    private function annualCapRefusal(\DateTimeImmutable $now): SmsCapRefusedException
    {
        return new SmsCapRefusedException("SMS allowance for FY {$this->smsAllowance->financialYear($now)} exhausted");
    }
}
