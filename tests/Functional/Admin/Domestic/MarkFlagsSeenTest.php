<?php

namespace App\Tests\Functional\Admin\Domestic;

use App\Controller\Admin\Domestic\SurveyController;
use App\Entity\Domestic\SurveyResponse;
use App\Tests\DataFixtures\Domestic\ResponseFixtures;
use App\Tests\DataFixtures\Domestic\SurveyClosedFixtures;
use App\Tests\Functional\Admin\AbstractAdminFunctionalTestCase;

class MarkFlagsSeenTest extends AbstractAdminFunctionalTestCase
{
    private const FIXTURES = [SurveyClosedFixtures::class, ResponseFixtures::class];

    public function testButtonTogglesSeenStateBackAndForth(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin(self::FIXTURES);
        $surveyId = $this->giveResponseFlags(2);
        $viewUrl = $this->viewUrl($surveyId);

        // Unreviewed to start: the "mark reviewed" button is shown.
        $browser->request('GET', $viewUrl);
        self::assertEquals(200, $browser->getResponse()->getStatusCode());
        self::assertStringContainsString('Mark flags as reviewed', $browser->getResponse()->getContent());

        // Submit the form → flags become reviewed; button flips to the un-mark action.
        $this->submitMarkSeenForm($browser);
        self::assertStringContainsString('Mark flags as unreviewed', $browser->getResponse()->getContent());
        self::assertTrue($this->reloadResponse()->getQualityFlagsSeen());

        // Submit again → back to unreviewed.
        $this->submitMarkSeenForm($browser);
        self::assertStringContainsString('Mark flags as reviewed', $browser->getResponse()->getContent());
        self::assertFalse($this->reloadResponse()->getQualityFlagsSeen());
    }

    public function testNoButtonWhenSurveyHasNoFlags(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin(self::FIXTURES);
        // Leave qualityFlagCount null (no flags).
        $surveyId = $this->reloadResponse()->getSurvey()->getId();

        $browser->request('GET', $this->viewUrl($surveyId));

        self::assertEquals(200, $browser->getResponse()->getStatusCode());
        self::assertStringNotContainsString('Mark flags as reviewed', $browser->getResponse()->getContent());
    }

    public function testListShowsGreenTagForReviewedAndRedForUnreviewed(): void
    {
        $browser = $this->getBrowserLoadFixturesAndLogin(self::FIXTURES);

        $this->giveResponseFlags(2);
        $browser->request('GET', '/csrgt/surveys-ni/');
        self::assertStringContainsString('govuk-tag--red', $browser->getResponse()->getContent());

        $response = $this->reloadResponse();
        $response->setQualityFlagsSeen(true);
        $this->getEntityManager()->flush();

        $browser->request('GET', '/csrgt/surveys-ni/');
        self::assertStringContainsString('govuk-tag--green', $browser->getResponse()->getContent());
    }

    private function giveResponseFlags(int $count): string
    {
        $response = $this->reloadResponse();
        $response->setQualityFlagCount($count);
        $this->getEntityManager()->flush();

        return $response->getSurvey()->getId();
    }

    private function reloadResponse(): SurveyResponse
    {
        $em = $this->getEntityManager();
        $em->clear();

        return $em->getRepository(SurveyResponse::class)->findOneBy([]);
    }

    private function viewUrl(string $surveyId): string
    {
        return static::getContainer()->get('router')
            ->generate(SurveyController::VIEW_ROUTE, ['surveyId' => $surveyId]);
    }

    private function submitMarkSeenForm(\Symfony\Bundle\FrameworkBundle\KernelBrowser $browser): void
    {
        $form = $browser->getCrawler()->filter('form[action*="mark-seen"]')->form();
        $browser->submit($form);
    }
}
