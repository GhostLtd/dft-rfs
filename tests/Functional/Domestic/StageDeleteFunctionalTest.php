<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\DayStopFixtures;

class StageDeleteFunctionalTest extends AbstractStageFunctionalTest
{
    public function testStageDeletion()
    {
        $bognor = 'Bognor Regis — loaded';
        $worthing = 'Worthing — loaded';

        $browser = $this->getBrowserAndLoadFixtures([DayStopFixtures::class]);
        $this->login($browser);
        $this->clickSummaryListActionLink($browser, "5 stops or fewer (Bognor Regis to Bognor Regis)");

        self::assertEquals($bognor, $this->getStageStartingLocation($browser, 'Stage 1'));
        self::assertEquals($worthing, $this->getStageStartingLocation($browser, 'Stage 2'));

        $this->clickLink($browser, 'Delete Stage 1', 'govuk-button');

        $browser->submitForm('Yes, delete this stage');

        self::assertEquals($worthing, $this->getStageStartingLocation($browser, 'Stage 1'));;
        self::assertEquals(1, $this->countStages($browser));
    }

    public function testStageDeletionOnNonExistentDay()
    {
        $browser = $this->getBrowserAndLoadFixtures([DayStopFixtures::class]);
        $this->login($browser);

        $browser->request('GET', "https://{$_ENV['FRONTEND_HOSTNAME']}/domestic-survey/day-2/delete-day-stop-1");
        self::assertEquals(404, $browser->getResponse()->getStatusCode());
    }
}