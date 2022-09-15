<?php

namespace App\Tests\Functional\Domestic;

use App\Tests\Functional\AbstractFrontendFunctionalTest;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractStageFunctionalTest extends AbstractFrontendFunctionalTest
{
    protected function getStageStartingLocation(KernelBrowser $browser, string $stage): string
    {
        return $this->startingLocations($browser, $stage)->text('', true);
    }

    protected function countStages(KernelBrowser $browser): int
    {
        return $this->startingLocations($browser)->count();
    }

    private function startingLocations(KernelBrowser $browser, string $stage = null): Crawler
    {
        $xpathHeader = ($stage === null) ? "h2" : "h2[normalize-space()='{$stage}']";

        return $browser
            ->getCrawler()
            ->filterXPath(
                "//main/{$xpathHeader}/following-sibling::div[1]/dl/div/".
                $this->summaryListPart('key', "Start location").
                "/../".
                $this->summaryListPart('value')
            );
    }
}