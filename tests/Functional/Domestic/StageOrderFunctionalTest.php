<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\DayStopFixtures;

class StageOrderFunctionalTest extends AbstractStageFunctionalTest
{
    public function testStageReordering()
    {
        $bognor = 'Bognor Regis — loaded';
        $worthing = 'Worthing — loaded';

        $this->loadFixtures([DayStopFixtures::class]);
        $this->login($this->browser);

        $this->clickSummaryListActionLink($this->browser, "5 stops or fewer (Bognor Regis to Bognor Regis)");

        self::assertEquals($bognor, $this->getStageStartingLocation($this->browser, 'Stage 1'));
        self::assertEquals($worthing, $this->getStageStartingLocation($this->browser, 'Stage 2'));

        $this->clickLink($this->browser, 'Re-order stages', 'govuk-button');
        $this->clickLink($this->browser, 'Move down: stage #1 - Bognor Regis to Chichester', 'govuk-link');

        $this->browser->submitForm('Save order');

        self::assertEquals($worthing, $this->getStageStartingLocation($this->browser, 'Stage 1'));
        self::assertEquals($bognor, $this->getStageStartingLocation($this->browser, 'Stage 2'));
    }
}