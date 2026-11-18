<?php

namespace App\Tests\NewFunctional\Wizard\Domestic;

use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\NewFunctional\Wizard\AbstractPasscodeWizardTestCase;

/**
 * Requests the day pages weren't built for, which used to give 500s (found by DAST: local/2026-11-17-dast-bug-log.md,
 * B10 and B4). The fixtures' survey has day 1 (with stops) and no day 3.
 */
class DomesticSurveyDayRobustnessTest extends AbstractPasscodeWizardTestCase
{
    /**
     * The "how many stops" form, submitted without its button (as form.submit() does, or anything else that
     * submits a form without clicking one): treated like Continue, not a crash.
     */
    public function testAddDayFormSubmittedWithoutAButtonContinues(): void
    {
        $this->initialiseTest([DayStopFixtures::class]);

        $this->submitAddDayFormWithoutAButton(3, '0'); // fewer than 5 stops

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
        $this->assertPathMatches('#^/domestic-survey/day-3/stop-add/#', true);
    }

    public function testAddDayFormSubmittedWithoutAButtonOrAnswerShowsTheFormAgain(): void
    {
        $this->initialiseTest([DayStopFixtures::class]);

        $this->submitAddDayFormWithoutAButton(3, null);

        // 422: Symfony's render() of a submitted, invalid form (as when Continue is clicked with no answer)
        $this->assertSame(422, $this->client->getResponse()->getStatusCode());
        $this->assertPathMatches('/domestic-survey/day-3/add', false);
    }

    public function testReorderStopsForADayThatDoesNotExistIsNotFound(): void
    {
        $this->initialiseTest([DayStopFixtures::class]);

        $this->client->request('GET', '/domestic-survey/day-3/reorder-stops');

        $this->assertSame(404, $this->client->getResponse()->getStatusCode());
    }

    /** Control: reordering an existing day's stops still works */
    public function testReorderStopsForAnExistingDayWorks(): void
    {
        $this->initialiseTest([DayStopFixtures::class]);

        $this->client->request('GET', '/domestic-survey/day-1/reorder-stops');

        $this->assertSame(200, $this->client->getResponse()->getStatusCode());
    }

    private function submitAddDayFormWithoutAButton(int $dayNumber, ?string $hasMoreThanFiveStops): void
    {
        $crawler = $this->client->request('GET', "/domestic-survey/day-{$dayNumber}/add");
        $this->assertSame(200, $this->client->getResponse()->getStatusCode());

        // A Form built from the <form> element itself has no submit button, so none is sent
        $form = $crawler->filter('form[method="post"]')->form();
        if ($hasMoreThanFiveStops !== null) {
            $form->setValues(['number_of_stops[hasMoreThanFiveStops]' => $hasMoreThanFiveStops]);
        }
        foreach (array_keys($form->getValues()) as $name) {
            $this->assertStringNotContainsString('[continue]', $name);
            $this->assertStringNotContainsString('[cancel]', $name);
        }

        $this->client->submit($form);
    }
}
