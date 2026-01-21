<?php

namespace App\Tests\Utility;

use App\Entity\Domestic\Survey;
use App\Entity\EmailBlacklist;
use App\Entity\Embeddable\LongAddress;

class NotificationInterceptionServiceBlacklistTest extends AbstractNotificationInterceptionServiceTest
{
    public function testGetAllEmailsForSurveyFiltersBlacklistedEmails(): void
    {
        $blacklistedEmail = (new EmailBlacklist())
            ->setEmail('blocked@example.com')
            ->setReason('Test blacklist')
            ->setCreatedAt(new \DateTime());
        $this->entityManager->persist($blacklistedEmail);
        $this->entityManager->flush();

        $surveyPeriodStart = new \DateTime();
        $surveyPeriodEnd = (clone $surveyPeriodStart)->modify('+6 days');

        $survey = new Survey();
        $survey->setRegistrationMark('AB12CDE');
        $survey->setInvitationAddress(
            (new LongAddress())
                ->setLine1('Test Company')
                ->setPostcode('AB1 2CD')
        );
        $survey->setInvitationEmails('allowed@example.com, blocked@example.com, another@example.com');
        $survey->setState(Survey::STATE_NEW);
        $survey->setIsNorthernIreland(false);
        $survey->setSurveyPeriodStart($surveyPeriodStart);
        $survey->setSurveyPeriodEnd($surveyPeriodEnd);

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        $emails = $this->interceptService->getAllEmailsForSurvey($survey);

        $this->assertNotContains('blocked@example.com', $emails);
        $this->assertContains('allowed@example.com', $emails);
        $this->assertContains('another@example.com', $emails);
        $this->assertCount(2, $emails);
    }

    public function testGetAllEmailsForSurveyWithNoBlacklistedEmails(): void
    {
        $surveyPeriodStart = new \DateTime();
        $surveyPeriodEnd = (clone $surveyPeriodStart)->modify('+6 days');

        $survey = new Survey();
        $survey->setRegistrationMark('AB12CDE');
        $survey->setInvitationAddress(
            (new LongAddress())
                ->setLine1('Test Company')
                ->setPostcode('AB1 2CD')
        );
        $survey->setInvitationEmails('allowed@example.com, another@example.com');
        $survey->setState(Survey::STATE_NEW);
        $survey->setIsNorthernIreland(false);
        $survey->setSurveyPeriodStart($surveyPeriodStart);
        $survey->setSurveyPeriodEnd($surveyPeriodEnd);

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        $emails = $this->interceptService->getAllEmailsForSurvey($survey);

        $this->assertContains('allowed@example.com', $emails);
        $this->assertContains('another@example.com', $emails);
        $this->assertCount(2, $emails);
    }

    public function testGetAllEmailsForSurveyFiltersMultipleBlacklistedEmails(): void
    {
        $blacklistedEmail1 = (new EmailBlacklist())
            ->setEmail('blocked1@example.com')
            ->setReason('Test blacklist 1')
            ->setCreatedAt(new \DateTime());
        $this->entityManager->persist($blacklistedEmail1);

        $blacklistedEmail2 = (new EmailBlacklist())
            ->setEmail('blocked2@example.com')
            ->setReason('Test blacklist 2')
            ->setCreatedAt(new \DateTime());
        $this->entityManager->persist($blacklistedEmail2);

        $this->entityManager->flush();

        $surveyPeriodStart = new \DateTime();
        $surveyPeriodEnd = (clone $surveyPeriodStart)->modify('+6 days');

        $survey = new Survey();
        $survey->setRegistrationMark('AB12CDE');
        $survey->setInvitationAddress(
            (new LongAddress())
                ->setLine1('Test Company')
                ->setPostcode('AB1 2CD')
        );
        $survey->setInvitationEmails('allowed@example.com, blocked1@example.com, blocked2@example.com, another@example.com');
        $survey->setState(Survey::STATE_NEW);
        $survey->setIsNorthernIreland(false);
        $survey->setSurveyPeriodStart($surveyPeriodStart);
        $survey->setSurveyPeriodEnd($surveyPeriodEnd);

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        $emails = $this->interceptService->getAllEmailsForSurvey($survey);

        $this->assertNotContains('blocked1@example.com', $emails);
        $this->assertNotContains('blocked2@example.com', $emails);
        $this->assertContains('allowed@example.com', $emails);
        $this->assertContains('another@example.com', $emails);
        $this->assertCount(2, $emails);
    }

    public function testGetAllEmailsForSurveyFiltersBlacklistedEmailsCaseInsensitive(): void
    {
        $blacklistedEmail = (new EmailBlacklist())
            ->setEmail('BLOCKED@EXAMPLE.COM')
            ->setReason('Test blacklist with uppercase')
            ->setCreatedAt(new \DateTime());
        $this->entityManager->persist($blacklistedEmail);
        $this->entityManager->flush();

        $surveyPeriodStart = new \DateTime();
        $surveyPeriodEnd = (clone $surveyPeriodStart)->modify('+6 days');

        $survey = new Survey();
        $survey->setRegistrationMark('AB12CDE');
        $survey->setInvitationAddress(
            (new LongAddress())
                ->setLine1('Test Company')
                ->setPostcode('AB1 2CD')
        );
        $survey->setInvitationEmails('allowed@example.com, blocked@example.com, Blocked@Example.Com');
        $survey->setState(Survey::STATE_NEW);
        $survey->setIsNorthernIreland(false);
        $survey->setSurveyPeriodStart($surveyPeriodStart);
        $survey->setSurveyPeriodEnd($surveyPeriodEnd);

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        $emails = $this->interceptService->getAllEmailsForSurvey($survey);

        $this->assertNotContains('blocked@example.com', $emails);
        $this->assertNotContains('Blocked@Example.Com', $emails);
        $this->assertContains('allowed@example.com', $emails);
        $this->assertCount(1, $emails);
    }

    public function testGetAllEmailsForSurveyWhenAllEmailsBlacklisted(): void
    {
        $blacklistedEmail1 = (new EmailBlacklist())
            ->setEmail('blocked1@example.com')
            ->setReason('Test blacklist 1')
            ->setCreatedAt(new \DateTime());
        $this->entityManager->persist($blacklistedEmail1);

        $blacklistedEmail2 = (new EmailBlacklist())
            ->setEmail('blocked2@example.com')
            ->setReason('Test blacklist 2')
            ->setCreatedAt(new \DateTime());
        $this->entityManager->persist($blacklistedEmail2);

        $this->entityManager->flush();

        $surveyPeriodStart = new \DateTime();
        $surveyPeriodEnd = (clone $surveyPeriodStart)->modify('+6 days');

        $survey = new Survey();
        $survey->setRegistrationMark('AB12CDE');
        $survey->setInvitationAddress(
            (new LongAddress())
                ->setLine1('Test Company')
                ->setPostcode('AB1 2CD')
        );
        $survey->setInvitationEmails('blocked1@example.com, blocked2@example.com');
        $survey->setState(Survey::STATE_NEW);
        $survey->setIsNorthernIreland(false);
        $survey->setSurveyPeriodStart($surveyPeriodStart);
        $survey->setSurveyPeriodEnd($surveyPeriodEnd);

        $this->entityManager->persist($survey);
        $this->entityManager->flush();

        $emails = $this->interceptService->getAllEmailsForSurvey($survey);

        $this->assertEmpty($emails);
    }
}
