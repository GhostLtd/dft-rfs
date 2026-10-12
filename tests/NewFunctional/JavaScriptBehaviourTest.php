<?php

namespace App\Tests\NewFunctional;

use App\Entity\Domestic\DayStop;
use App\Entity\PasscodeUser;
use App\Tests\DataFixtures\Domestic\DayStopFixtures;
use App\Tests\DataFixtures\Domestic\SurveyFixtures;
use App\Utility\PasscodeGenerator;
use Facebook\WebDriver\Exception\TimeoutException;
use Facebook\WebDriver\WebDriverBy;

/**
 * Every test that needs a real browser lives in this one class.
 *
 * The rest of the functional suite runs in-process (see AbstractFunctionalTestCase), so this is
 * the only place that our compiled JavaScript bundles actually run. Panther applies web-server
 * options on the first createPantherClient() call of a run and keeps that server for the rest
 * of the run, so a second Panther class would silently inherit this one's options (or vice
 * versa). Every test here therefore passes the same SERVER_OPTIONS.
 *
 * N.B. These read public/build, so run `yarn build` after touching anything under assets/.
 */
class JavaScriptBehaviourTest extends AbstractPantherTestCase
{
    private const array SERVER_OPTIONS = [
        'hostname' => 'rfs-frontend.localhost',
        // Only the admin firewall (host-matched) uses this
        'env' => ['TEST_GOOGLE_IAP_AUTH_USER' => 'test@example.com'],
    ];

    /**
     * Frontend bundle (app.js) smoke test.
     *
     * The conditional reveal is rendered already collapsed (__conditional--hidden) and the inline
     * script in the base template adds `js-enabled` without the bundle, so neither proves anything.
     * aria-expanded is only ever added by govuk-frontend's Radios JS, and the click checks that the
     * reveal actually works.
     */
    public function testFrontendBundleConditionalReveal(): void
    {
        $this->initialiseClientAndLoadFixtures([SurveyFixtures::class], self::SERVER_OPTIONS);
        $this->passcodeLogin();

        $this->client->request('GET', '/domestic-survey/initial-details/introduction');
        $this->runAndWaitForNavigation(fn() => $this->client->submitForm('form_button_group_continue'));
        $this->runAndWaitForNavigation(fn() => $this->client->submitForm('contact_details_button_group_continue', [
            'contact_details[contactName]' => 'Mark',
            'contact_details[contactEmail]' => 'mark@example.com',
        ]));
        $this->assertPathMatches('/domestic-survey/initial-details/in-possession', false);

        $this->assertConditionalRevealWorks('in_possession_of_vehicle_isInPossessionOfVehicle');
    }

    /**
     * Admin bundle (admin.js) smoke test - as above.
     */
    public function testAdminBundleConditionalReveal(): void
    {
        $this->initialiseClientAndLoadFixtures([DayStopFixtures::class], self::SERVER_OPTIONS);
        $this->requestAdminDayStopPage();

        $this->assertConditionalRevealWorks('day_stop_goodsDescription');
    }

    /**
     * Radios with `data-aria-hide-controls` (the bundle's GhostRadios): choosing one hides the fields
     * it names, and choosing another radio in the group shows them again.
     *
     * Asserted on what is actually displayed, not on the classes or attributes the script uses to hide
     * things - those could be set correctly while broken or missing CSS leaves the fields visible.
     */
    public function testRadiosHideControls(): void
    {
        $this->initialiseClientAndLoadFixtures([DayStopFixtures::class], self::SERVER_OPTIONS);
        $this->requestAdminDayStopPage();

        $hidingRadio = $this->client->findElement(WebDriverBy::cssSelector('input[type=radio][data-aria-hide-controls]:not([data-aria-hide-controls=""])'));
        $otherRadio = $this->client->findElement(WebDriverBy::xpath(
            '//input[@type="radio"][@name=' . $this->xpathQuote($hidingRadio->getAttribute('name')) . '][not(@data-aria-hide-controls) or @data-aria-hide-controls=""]'
        ));

        $targetIds = json_decode($hidingRadio->getAttribute('data-aria-hide-controls'), true);
        $this->assertNotEmpty($targetIds);

        $areHidden = function (bool $hidden) use ($targetIds): bool {
            foreach ($targetIds as $targetId) {
                if ($hidden === $this->isDisplayed($targetId)) {
                    return false;
                }
            }
            return true;
        };

        $otherRadio->click();
        $this->waitUntil(fn() => $areHidden(false), 'Expected the hide-controls targets to be shown');

        $hidingRadio->click();
        $this->waitUntil(fn() => $areHidden(true), 'Expected the hide-controls targets to be hidden');

        $otherRadio->click();
        $this->waitUntil(fn() => $areHidden(false), 'Expected the hide-controls targets to be shown again');
    }

    /**
     * Finds the first radio in the group that controls a conditional reveal, and checks that govuk-frontend
     * has enhanced it (aria-expanded, which the template never renders), and that the reveal is hidden when a
     * sibling is chosen and displayed when the radio is.
     */
    protected function assertConditionalRevealWorks(string $radioIdPrefix): void
    {
        // govuk-frontend promotes data-aria-controls to aria-controls (removing the former) as it initialises
        $radio = $this->client->findElement(WebDriverBy::cssSelector(
            "input[type=radio][id^='{$radioIdPrefix}'][data-aria-controls]:not([data-aria-controls='']), " .
            "input[type=radio][id^='{$radioIdPrefix}'][aria-controls]:not([aria-controls=''])"
        ));
        $radioId = $radio->getAttribute('id');
        $revealId = $radio->getAttribute('aria-controls') ?? $radio->getAttribute('data-aria-controls');

        $sibling = $this->client->findElement(WebDriverBy::xpath(
            '//input[@type="radio"][@name=' . $this->xpathQuote($radio->getAttribute('name')) . '][@id!=' . $this->xpathQuote($radioId) . ']'
        ));

        $sibling->click();
        $this->waitForAttribute($radioId, 'aria-expanded', fn(?string $v) => $v === 'false');
        $this->assertFalse($this->isDisplayed($revealId), 'Expected the conditional reveal to be hidden');

        $radio->click();
        $this->waitForAttribute($radioId, 'aria-expanded', fn(?string $v) => $v === 'true');
        $this->assertTrue($this->isDisplayed($revealId), 'Expected the conditional reveal to be displayed');
    }

    protected function waitForAttribute(string $elementId, string $attribute, callable $predicate): void
    {
        try {
            $this->client->getWebDriver()->wait(self::WAIT_TIMEOUT)->until(
                fn() => $predicate($this->client->findElement(WebDriverBy::id($elementId))->getAttribute($attribute))
            );
        } catch (TimeoutException) {
            $actual = $this->client->findElement(WebDriverBy::id($elementId))->getAttribute($attribute);
            $this->fail(
                "Timed out waiting for #{$elementId}[{$attribute}] (currently: " . var_export($actual, true) . "). " .
                "Is the JavaScript bundle broken, or is public/build stale? (try `yarn build`)"
            );
        }
    }

    protected function waitUntil(callable $condition, string $message): void
    {
        try {
            $this->client->getWebDriver()->wait(self::WAIT_TIMEOUT)->until(fn() => $condition());
            $this->addToAssertionCount(1);
        } catch (TimeoutException) {
            $this->fail("{$message}. Is the JavaScript bundle broken, or is public/build stale? (try `yarn build`)");
        }
    }

    protected function isDisplayed(string $elementId): bool
    {
        return $this->client->findElement(WebDriverBy::id($elementId))->isDisplayed();
    }

    protected function passcodeLogin(): void
    {
        $user = $this->fixtureReferenceRepository->getReference('user:frontend', PasscodeUser::class);
        $password = static::getContainer()->get(PasscodeGenerator::class)->getPasswordForUser($user);

        $this->client->request('GET', '/login');
        $this->runAndWaitForNavigation(fn() => $this->client->submitForm('passcode_login_sign_in', [
            'passcode_login[passcode][0]' => $user->getUsername(),
            'passcode_login[passcode][1]' => $password,
        ]));
    }

    protected function requestAdminDayStopPage(): void
    {
        $dayStop = $this->fixtureReferenceRepository->getReference('day-stop:simple:1', DayStop::class);

        // The server listens for both hostnames (*.localhost resolves to loopback), so the admin
        // site is reached by swapping the host on the frontend's base URI.
        $port = parse_url(static::$baseUri, PHP_URL_PORT);
        $this->client->request('GET', "http://rfs-admin.localhost:{$port}/csrgt/day-stages/{$dayStop->getId()}");

        $this->assertPathMatches("/csrgt/day-stages/{$dayStop->getId()}", false);
    }

    protected function xpathQuote(string $value): string
    {
        return str_contains($value, "'") ? "\"{$value}\"" : "'{$value}'";
    }
}
