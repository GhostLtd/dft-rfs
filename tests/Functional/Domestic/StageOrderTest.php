<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\DataFixtures\DayStopFixtures;
use App\Tests\Functional\AbstractFrontendFunctionalTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;

class StageOrderTest extends AbstractFrontendFunctionalTest
{
    public function testStageReordering()
    {
        $bognor = 'Bognor Regis — loaded';
        $worthing = 'Worthing — loaded';

        $browser = $this->getBrowserAndLoadFixtures([DayStopFixtures::class]);
        $this->login($browser);
        $this->clickSummaryListActionLink($browser, "5 stops or fewer (Bognor Regis to Bognor Regis)");

        self::assertEquals($bognor, $this->getStageStartingLocation($browser, 'Stage 1'));
        self::assertEquals($worthing, $this->getStageStartingLocation($browser, 'Stage 2'));

        $this->clickLink($browser, 'Re-order stages', 'govuk-button');
        $this->clickLink($browser, 'Move down: stage #1 - Bognor Regis to Chichester', 'govuk-link');

        $browser->submitForm('Save order');

        self::assertEquals($worthing, $this->getStageStartingLocation($browser, 'Stage 1'));
        self::assertEquals($bognor, $this->getStageStartingLocation($browser, 'Stage 2'));
    }

    protected function getStageStartingLocation(KernelBrowser $browser, string $stage): string
    {
        return $browser
            ->getCrawler()
            ->filterXPath(
                "//main/h2[normalize-space()='{$stage}']/following-sibling::div[1]/dl/div/".
                $this->summaryListPart('key', "Start location").
                "/../".
                $this->summaryListPart('value')
            )
            ->text();
    }
}