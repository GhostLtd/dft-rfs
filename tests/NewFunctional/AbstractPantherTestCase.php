<?php

namespace App\Tests\NewFunctional;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Facebook\WebDriver\Exception\StaleElementReferenceException;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\Exception\UnknownErrorException;
use Facebook\WebDriver\WebDriverBy;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\PantherTestCase;
use Symfony\Component\Panther\ServerExtensionLegacy;

/**
 * Base class for tests that drive a real browser through Panther.
 *
 * Almost nothing needs this. A WebDriver interaction costs far more than the request behind it,
 * so the default is AbstractFunctionalTestCase (in-process, BrowserKit), and only tests that
 * genuinely depend on JavaScript or real rendering belong here - currently JavaScriptBehaviourTest,
 * which must remain the only subclass (see its docblock).
 *
 * The page-inspection helpers come from FunctionalHelpersTrait and work unchanged on Panther; what
 * remains here is the WebDriver-specific surface.
 */
class AbstractPantherTestCase extends PantherTestCase
{
    use FunctionalHelpersTrait;

    public const int WAIT_TIMEOUT = 5;
    protected Client $client;

    protected ReferenceRepository $fixtureReferenceRepository;

    /**
     * @param array $serverOptions The same shape as AbstractFunctionalTestCase's ('hostname', 'env'). N.B. Panther
     *                             only applies these when it starts its web server, on the first
     *                             createPantherClient() of a run.
     */
    public function initialiseClientAndLoadFixtures(array $fixtures, array $serverOptions = []): void
    {
        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->fixtureReferenceRepository = $databaseTool->loadFixtures($fixtures)->getReferenceRepository();

        $this->client = static::createPantherClient($serverOptions);
    }

    #[\Override]
    public function getCurrentUri(): string
    {
        return $this->client->getCurrentURL();
    }

    /**
     * Run a callable that triggers a page navigation, then block until the navigation actually
     * completes (or up to WAIT_TIMEOUT seconds). Also refreshes Panther's cached Crawler so
     * subsequent getCrawler() calls don't try to traverse now-stale old-page elements.
     *
     * Panther's Client::click() and Client::submit() only wait for navigation on Firefox; on
     * Chrome they return immediately, racing the page load. Chrome 147 is fast enough that the
     * race virtually always bites. ChromeDriver 121+ also has a regression where staleness
     * sometimes surfaces as an "unknown error" instead of a proper StaleElementReferenceException
     * (see https://github.com/SeleniumHQ/selenium/issues/15401) — both exception flavours are
     * accepted as the success signal here.
     *
     * (Off-browser, BrowserKit is synchronous and this isn't needed)
     */
    protected function runAndWaitForNavigation(callable $action): void
    {
        $previousHtml = $this->client->getWebDriver()->findElement(WebDriverBy::cssSelector('html'));
        $action();
        try {
            $this->client->getWebDriver()->wait(self::WAIT_TIMEOUT)->until(function () use ($previousHtml): bool {
                try {
                    $previousHtml->isEnabled();
                    return false;
                } catch (StaleElementReferenceException) {
                    return true;
                } catch (UnknownErrorException $e) {
                    if (str_contains($e->getMessage(), 'Node with given id does not belong to the document')) {
                        return true;
                    }
                    throw $e;
                }
            });
            $this->client->refreshCrawler();
        } catch (TimeoutException) {
            // No navigation (e.g. AJAX/no-op submit). Carry on.
        }
    }

    /**
     * Overridden from PantherTestCase, as some test names have unusable characters in
     */
    #[\Override]
    public function takeScreenshotIfTestFailed(): void
    {
        $status = $this->status();
        if (!$status->isError() && !$status->isFailure()) {
            return;
        }

        $type = $status->isFailure() ? 'failure' : 'error';
        $test = md5($this->toString());

        // It's deprecated, but at least phpstan can detect the method on the class
        ServerExtensionLegacy::takeScreenshots($type, $test);
    }
}
