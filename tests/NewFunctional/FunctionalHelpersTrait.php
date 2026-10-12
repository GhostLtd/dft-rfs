<?php

namespace App\Tests\NewFunctional;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Page-inspection and assertion helpers shared by the functional test base classes.
 *
 * Everything here goes through BrowserKit's AbstractBrowser and DomCrawler APIs. Panther's
 * Client and Crawler extend those, so the helpers behave the same whether the test drives
 * the app in-process (AbstractFunctionalTestCase) or a real browser (AbstractPantherTestCase).
 *
 * @property \Symfony\Component\BrowserKit\AbstractBrowser $client
 */
trait FunctionalHelpersTrait
{
    /**
     * The URL of the page currently loaded. Panther and KernelBrowser expose this
     * differently, so the base classes supply it.
     */
    abstract public function getCurrentUri(): string;

    public function assertPathMatches(string $expectedPath, bool $isRegex, ?string $errorMessage = null): void
    {
        $errorMessage ??= 'Page path does not match'. ($isRegex ? ' regex' : '');
        $this->doAssertPathMatches($expectedPath, $isRegex, false, $errorMessage);
    }

    public function assertPathNotMatches(string $expectedPath, bool $isRegex, ?string $errorMessage = null): void
    {
        $errorMessage ??= 'Page path does not match'. ($isRegex ? ' regex' : '');
        $this->doAssertPathMatches($expectedPath, $isRegex, true, $errorMessage);
    }

    protected function doAssertPathMatches(string $expectedPath, bool $isRegex, bool $negate = false, string $errorMessage = 'Page path does not match regex'): void
    {
        $functionMap = [
            'Regex' => 'assertMatchesRegularExpression',
            'NotRegex' => 'assertDoesNotMatchRegularExpression',
            'Equals' => 'assertEquals',
            'NotEquals' => 'assertNotEquals',
        ];

        $func = $functionMap[($negate ? 'Not' : '') . ($isRegex ? 'Regex' : 'Equals')];
        $this->$func($expectedPath, $this->getCurrentPath(), $errorMessage);
    }

    protected function getCurrentPath(): string
    {
        return parse_url($this->getCurrentUri(), PHP_URL_PATH);
    }

    protected function getHeading(string $tagName='h1'): string
    {
        return $this->client->getCrawler()->filterXPath('//*[@id="main-content"]/'.$tagName)->text();
    }

    protected function getTableData(string $xpathToTableRows = '//*[@id="main-content"]/table/tbody/tr'): array
    {
        // N.B. children() rather than filterXPath('./*'): DomCrawler rewrites a leading './' as 'self::',
        //      which would return the row itself rather than its cells.
        return $this->client->getCrawler()->filterXPath($xpathToTableRows)->each(
            fn(Crawler $tableRow) => $tableRow->children()->each(fn(Crawler $cell) => $cell->text())
        );
    }

    protected function getSummaryListData(string $xpathToSummaryListRows = '//*[@id="main-content"]/dl/div'): array
    {
        return $this->getTableData($xpathToSummaryListRows);
    }

    protected function clickLinkContaining(string $textContains, int $index = 0, string $xpathBase = '//'): void
    {
        $textContains = str_replace("'", "\'", $textContains);
        $xpath = $xpathBase.'a[contains(., "' . $textContains . '")]';

        $links = $this->client->getCrawler()->filterXPath($xpath)->links();
        $link = $links[$index] ?? null;

        if ($link === null) {
            $this->fail("No such link found (contains: \"$textContains\", index: $index)");
        }

        $this->client->click($link);
    }

    protected function getElementByTextContains(string $elementType, string $textContains, int $elementNumber = 0): Crawler
    {
        $textContains = str_replace("'", "\'", $textContains);
        $elements = $this->client->getCrawler()->filterXPath("//".$elementType."[contains(text(), '" . $textContains . "')]");

        if ($elements->count() <= $elementNumber) {
            $this->fail("Element #{$elementNumber} with text containing '{$textContains}' not found");
        }

        return $elements->eq($elementNumber);
    }

    protected function assertSummaryListData(array $expectedData): void
    {
        $summaryData = $this->getSummaryListData();
        foreach ($expectedData as $key => $expectedValue) {
            $this->assertSummaryData($summaryData, $key, $expectedValue);
        }
    }

    protected function assertSummaryData(array $summaryData, string $key, string $expectedValue): void
    {
        foreach ($summaryData as $summaryDataRow) {
            if (str_contains($summaryDataRow[0], $key)) {
                $this->assertEquals($expectedValue, $summaryDataRow[1]);
                return;
            }
        }
        $this->fail("Failed to find summaryData row with key '{$key}'");
    }

    /**
     * Summary-list values are compared as text with whitespace normalised (which is how DomCrawler reads
     * them), so a line break is invisible to assertSummaryListData() - <br> contributes no text at all.
     * Where a line break matters, assert on the value's markup with this.
     */
    protected function assertSummaryRowValueHtmlMatches(string $key, string $expectedHtmlRegex): void
    {
        $rows = $this->client->getCrawler()
            ->filterXPath('//*[@id="main-content"]/dl/div')
            ->reduce(fn(Crawler $row) => str_contains($row->children()->first()->text(), $key));

        if ($rows->count() === 0) {
            $this->fail("Failed to find summaryData row with key '{$key}'");
        }

        $this->assertMatchesRegularExpression($expectedHtmlRegex, trim($rows->first()->children()->eq(1)->html()));
    }

    protected function assertDataMatches(object $object, array $expectedData, string $path = 'root'): void
    {
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($expectedData as $key => $expectedValue) {
            $actualValue = $accessor->getValue($object, $key);
            $currentPath = "{$path}.{$key}";

            if (is_array($expectedValue)) {
                if (is_array($actualValue)) {
                    // PHPUnit 12's canonicalizing comparison preserves array keys (it no longer
                    // reindexes via sort()). For list-style values (e.g. countriesTransitted) the
                    // keys are positional artifacts, so compare by value only.
                    if (array_is_list($expectedValue)) {
                        $this->assertEqualsCanonicalizing($expectedValue, array_values($actualValue));
                    } else {
                        $this->assertEqualsCanonicalizing($expectedValue, $actualValue);
                    }
                    continue;
                } else if (!is_object($actualValue)) {
                    $type = gettype($actualValue);
                    $this->fail("Expected object at path {$currentPath}, but got {$type}");
                }

                $this->assertDataMatches($actualValue, $expectedValue, $currentPath);
            } else {
                $this->assertEquals($expectedValue, $actualValue, "Values do not match at path {$currentPath}");
            }
        }
    }
}
