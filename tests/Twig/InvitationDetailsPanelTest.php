<?php

namespace App\Tests\Twig;

use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyNotification;
use App\Entity\Embeddable\LongAddress;
use App\Utility\Notification\NotificationChannel;
use App\Utility\Notification\NotificationType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Twig\Environment;

/**
 * Renders admin/includes/invitation-details.html.twig directly (via the real twig service) to lock
 * in F2: rows are numbered per-channel, not across the whole invitation/reminder group, so a single
 * invite sent as both a letter and an email doesn't read as "Letter #2" / "Email #1".
 */
class InvitationDetailsPanelTest extends KernelTestCase
{
    private Environment $twig;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->twig = self::getContainer()->get('twig');
    }

    private function survey(): Survey
    {
        return (new Survey())
            ->setId('s1')
            ->setInvitationAddress((new LongAddress())->setLine1('Some Co')->setLine2('1 The Road')->setPostcode('BA1 1HR'));
    }

    private function notification(NotificationType $type, NotificationChannel $channel, string $sentAt): SurveyNotification
    {
        return (new SurveyNotification())->setType($type)->setChannel($channel)->setSentAt(new \DateTime($sentAt));
    }

    private function render(Survey $survey, bool $showEmails = true): string
    {
        return $this->twig->render('admin/includes/invitation-details.html.twig', [
            'survey' => $survey,
            'lcniInfo' => [],
            'showEmails' => $showEmails,
        ]);
    }

    /** Strips tags to plain, whitespace-collapsed text, guarding against adjacent tags merging words. */
    private function textOf(string $html): string
    {
        return trim(preg_replace('/\s+/', ' ', strip_tags(str_replace('<', ' <', $html))));
    }

    public function testSameSendLetterAndEmailAreNotNumbered(): void
    {
        $survey = $this->survey();
        $survey->addNotification($this->notification(NotificationType::INVITE, NotificationChannel::LETTER, '2026-01-01 10:00'));
        $survey->addNotification($this->notification(NotificationType::INVITE, NotificationChannel::EMAIL, '2026-01-01 10:00'));

        $text = $this->textOf($this->render($survey));

        self::assertMatchesRegularExpression('/\bLetter\b/', $text);
        self::assertMatchesRegularExpression('/\bEmail\b/', $text);
        self::assertDoesNotMatchRegularExpression('/Letter #/', $text);
        self::assertDoesNotMatchRegularExpression('/Email #/', $text);
    }

    public function testResentLetterIsNumberedWithinItsOwnChannel(): void
    {
        $survey = $this->survey();
        $survey->addNotification($this->notification(NotificationType::INVITE, NotificationChannel::LETTER, '2026-01-01 10:00'));
        $survey->addNotification($this->notification(NotificationType::INVITE, NotificationChannel::LETTER, '2026-01-08 10:00'));

        $text = $this->textOf($this->render($survey));

        self::assertStringContainsString('Letter #2', $text, 'newer letter gets the higher number');
        self::assertStringContainsString('Letter #1', $text, 'older letter gets the lower number');
    }

    public function testEmailHiddenAndInvitationGroupShowsDashWhenShowEmailsFalse(): void
    {
        $survey = $this->survey();
        $survey->addNotification($this->notification(NotificationType::INVITE, NotificationChannel::EMAIL, '2026-01-01 10:00'));

        $text = $this->textOf($this->render($survey, showEmails: false));

        self::assertMatchesRegularExpression('/Initial invitation\s*-/', $text, 'no visible rows once the only row (email) is hidden');
        self::assertStringNotContainsString('Email', $text);
    }
}
