<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\DayStopFixtures;

class StageDeleteFunctionalTest extends AbstractStageFunctionalTest
{
    public function testStageDeletion()
    {
        $bognor = 'Bognor Regis — loaded';
        $worthing = 'Worthing — loaded';
        
        $this->loadFixtures([DayStopFixtures::class]);
        $this->login($this->browser);

        $this->clickSummaryListActionLink($this->browser, "5 stops or fewer (Bognor Regis to Bognor Regis)");

        self::assertEquals($bognor, $this->getStageStartingLocation($this->browser, 'Stage 1'));
        self::assertEquals($worthing, $this->getStageStartingLocation($this->browser, 'Stage 2'));

        $this->clickLink($this->browser, 'Delete Stage 1', 'govuk-button');

        $this->browser->submitForm('Yes, delete this stage');

        self::assertEquals($worthing, $this->getStageStartingLocation($this->browser, 'Stage 1'));;
        self::assertEquals(1, $this->countStages($this->browser));
    }

    public function testStageDeletionOnNonExistentDay()
    {
        $this->loadFixtures([DayStopFixtures::class]);
        $this->login($this->browser);

        $this->browser->request('GET', "https://{$_ENV['FRONTEND_HOSTNAME']}/domestic-survey/day-2/delete-day-stop-1");
        self::assertEquals(404, $this->browser->getResponse()->getStatusCode());
    }
}