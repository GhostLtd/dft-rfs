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

        $this->clickSummaryListActionLink($browser, "5 stops or fewer (Bognor Regis to Bognor Regis)");

        $uri = $this->getLink($browser, 'Delete Stage 1', 'govuk-button')->getUri();
        $uri = str_replace('day-1', 'day-2', $uri);

        $browser->request('GET', $uri);

        self::assertEquals(404, $browser->getResponse()->getStatusCode());
    }
}