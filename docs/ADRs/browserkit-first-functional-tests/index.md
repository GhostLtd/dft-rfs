[Home](../../../README.md) > ADR: BrowserKit-first functional tests

# Decision record for BrowserKit-first functional tests

Title: Run functional tests in-process on BrowserKit, and use a real browser only where one is needed

This follows the same decision taken on NTS (`docs/ADRs/browserkit-first-functional-tests/index.md` in that repository), adapted to RFS. The plan, as reviewed, is in [BrowserKitFunctionalTestsPlan.md](../../BrowserKitFunctionalTestsPlan.md).

## Context and problem statement

RFS had two functional test trees:

- `tests/Functional`, which already ran in-process on `KernelBrowser` (`WebTestCase`)
- `tests/NewFunctional` (the wizard test framework), where every test drove a real Chrome through Symfony Panther

The cost, measured from a clean `.phpunit.cache/test-results`:

| Group | Tests | Time (summed) | Avg/test | Share |
|---|---|---|---|---|
| `tests/NewFunctional` (Panther) | 101 | 247.6s | 2.452s | 59% |
| `tests/Functional` (KernelBrowser) | 171 | 111.6s | 0.653s | 27% |
| Everything else | 2067 | 58.0s | 0.028s | 14% |

Full suite: 7:06 wall clock (2340 tests, 21373 assertions). (The plan quoted 219 `tests/Functional` tests from an older result cache, which had entries for tests that no longer exist; these figures are from a clean one.)

An audit of what the browser was buying us:

- **No assertion depended on JavaScript having run.** The two `executeScript` calls were test plumbing: saving and restoring form state between form test cases.
- **Every WebDriver call was page inspection** that DomCrawler does equally well.
- **No test exercised our own JavaScript.** The bundle loaded on every page, but nothing asserted that it worked. That was hiding a real bug (below).

The browser also caused a navigation race. On Chrome, Panther's `click()`/`submit()` return before navigation completes, which `runAndWaitForNavigation()` had to work around.

## Decision

1. `App\Tests\NewFunctional\AbstractFunctionalTestCase` is now in-process (`WebTestCase` / `KernelBrowser`) and is the default base class. The page-inspection helpers live in `FunctionalHelpersTrait`, written against BrowserKit's `AbstractBrowser` and DomCrawler, so they work on either client.
2. Panther is used only where a test genuinely depends on JavaScript or real rendering. `AbstractPantherTestCase` is the base for those, and **`JavaScriptBehaviourTest` is its only subclass**.
3. All browser tests stay in **one class**. Panther applies web-server options on the first `createPantherClient()` call of a run and keeps that server for the rest of the run, so a second Panther class would silently inherit the first one's configuration. Every test in the class passes the same `SERVER_OPTIONS`.
4. The JavaScript coverage the browser gave incidentally is replaced deliberately:
   - frontend (`app.js`) and admin (`admin.js`) smoke tests. These assert `aria-expanded`, which only govuk-frontend's Radios JS adds, and use WebDriver's `isDisplayed()` to check that clicking a radio really shows and hides its conditional reveal. A hidden *class* is not enough: broken or missing CSS could leave it set on content that is still visible, and a CSS change of exactly that kind passed the class-based version of these assertions. Neither `__conditional--hidden` (rendered by the template) nor `js-enabled` (added by an inline script) would prove that the bundle ran.
   - `data-aria-hide-controls` behaviour (the bundle's `GhostRadios`) on the admin day-stage form, also asserted with `isDisplayed()`

Panther's `ServerExtension` stays in `phpunit.xml.dist`. Its start-up hook only does bookkeeping; Chrome and the web server start only when a test creates a Panther client, so filtered or unit-only runs pay nothing.

## How the in-process client differs from Panther

- `HTTPS` is set on every request. `framework.session.cookie_secure` is `true`, and without HTTPS BrowserKit's cookie jar drops the session cookie.
- The host is set with `HTTP_HOST`, because routes are host-matched. Admin login is the `TEST_GOOGLE_IAP_AUTH_USER` server parameter, which `App\Security\GoogleIap\IapAuthenticator` reads from `$request->server`. Both are passed in `initialiseClientAndLoadFixtures()`'s `$serverOptions`, which has the same shape as Panther's options.
- **Form rewinding.** Every form test case after the first is submitted against the form as originally rendered, by re-parsing the HTML already in memory. Re-requesting the page is not equivalent: the wizard keeps partly-valid data in the session even when validation fails.
- **Checkboxes and radios** are set directly on the DOM (`applyChoiceSelections()`, which replaced `PantherBugWorkaround`). DomCrawler treats each `name[]` checkbox as a separate field and rejects `''` for a choice field. Unknown values are still rejected, as `Form::setValues()` would do. As with `setValues()`, `true` ticks a single checkbox whatever its value (one without a `value` attribute submits `on`); for a radio or checkbox group `true` is ambiguous and is rejected (`ApplyChoiceSelectionsTest`).
- **Status codes.** Each form submission now asserts `HTTP < 500`. Under Panther the framework could not see status codes at all.
- **Text is whitespace-normalised**, and `<br>` contributes no text. Where a line break matters, assert on the markup with `assertSummaryRowValueHtmlMatches()`; the trip trailer row and the pre-enquiry address do this.
- Off-browser tests **cannot tell shown content from hidden content**. DomCrawler reads all of the markup. Anything about visibility belongs in `JavaScriptBehaviourTest`.

## Consequences

What the conversion found:

- **`assets/js/radios.js` was dead under govuk-frontend 5.** It wrapped `Radios.prototype.init`, which v5 no longer has, so `data-aria-hide-controls` never hid anything. In the admin day-stage/summary form, choosing "Empty" goods left the hazardous goods, cargo type, weight and number-of-stops fields showing. `assets/js/common.js` now calls the frontend bundle's `ghostFrontendInitAll()` (as NTS does), which uses the maintained `GhostRadios` and also starts initialising the theme's show-password inputs. The RFS copies of `radios.js`, `double-submit-prevention.js` (identical to the bundle's) and `node-list.js` are removed.
- **`AuditLogRepository::getLogs()`** memoised in a function-`static`, which is process-wide: in a long-lived process (a messenger consumer, or these in-process tests) it went on serving the logs as they were at the first call. It is now an instance property, i.e. per request (`AuditLogRepositoryTest::testGetLogsMemoDoesNotOutliveTheRepositoryInstance`). No converted test happened to trip over it, but it is the same class of defect NTS's conversion found three of.
- **`Trip::getAllCountriesTransitted()`** passed a nullable property to `explode()`. That is deprecated since PHP 8.1 and will be a `TypeError` in PHP 9. Panther's separate server process never reported it.

Other consequences:

- **`yarn build` is a prerequisite** for `JavaScriptBehaviourTest`, which reads `public/build` (see the README's "Running the tests"). A timeout failure there names `yarn build` as the likeliest cause.
- Failures no longer produce screenshots, except from `JavaScriptBehaviourTest`, and the wizard framework's `DEBUG` levels 3–5 (screenshots) are gone. Instead, failures carry the real response and status code, and "Page path did not change" names any validation errors on the page.
- `runAndWaitForNavigation()` survives only on `AbstractPantherTestCase`. In-process navigation is synchronous.
- `KernelBrowser` reboots the kernel between requests, so entities read before a request are detached afterwards. `CallbackAction` already `clear()`s the entity manager before each callback.

## Outcome

Full suite measured on the same basis as the baseline (clean result cache, one run at a time):

| Group | Tests | Time (summed) | Avg/test | Share |
|---|---|---|---|---|
| `tests/NewFunctional` (in-process) | 101 | 89.4s | 0.886s | 34% |
| `JavaScriptBehaviourTest` (Panther) | 3 | 4.1s | 1.370s | 2% |
| `tests/Functional` (KernelBrowser) | 171 | 112.6s | 0.658s | 43% |
| Everything else | 2067 | 58.1s | 0.028s | 22% |

**7:06 → 4:34 wall clock** (2343 tests). `tests/NewFunctional` alone went from 4:23 to about 1:40.

## Verifying that the tests still test the same things

Checked on 2026-10-08.

**Inventory.** All 101 `tests/NewFunctional` tests are present before and after; none is missing and none was added, apart from `JavaScriptBehaviourTest`.

**Assertion counts.** Both suites were run with `--log-junit` and compared per test. The conversion deliberately adds assertions (one status check per form submission, plus the `<br>` markup checks), which could hide lost ones. So the comparison was done with those additions temporarily turned into no-ops. With that done, 99 of 101 tests have exactly the baseline count. The other two each have one *extra* assertion per `assertPageTitleContains()` call (AddSurveyTest +1, ReissueSurveyTest +3). Panther implements that assertion as a single `assertStringContainsString`; Symfony's DomCrawler version is `assertSelectorTextContains`, which counts its two constraints. No test lost an assertion.

**Deliberate bugs.** Each was applied on its own, the affected tests run, then reverted. All were caught:

| Bug | Caught by |
|---|---|
| Remove the `NotNull` on the early-response `is_correct` field | `Domestic/ClosingDetailsTest` (3 tests: missing validation error) |
| Reissue of a hired vehicle invites the wrong email address | `Admin/Domestic/ReissueSurveyTest` |
| Drop the `<br>` in the trip trailer summary row | `International/TripTest` (2 tests), and **only** through the new markup assertion, because the normalised text is identical |
| Vehicle "dates rejected" banner loses the registration mark | `International/VehicleTest` |
| RoRo login links expire immediately | `RoRo/LoginTest`, `RoRo/SurveyWizardTest` |
| A wizard form submission throws (HTTP 500) | `Domestic/ClosingDetailsTest`: "form submission returned HTTP 500" |
| Drop `nl2br` from the pre-enquiry address | `PreEnquiry/PreEnquiryTest` (both tests) |
| Comment out `ghostFrontendInitAll()` and rebuild | all three `JavaScriptBehaviourTest` tests |

The first mutation planned for reissue (dropping the explicit `persist()`) was not a bug: `setReissuedSurvey()` cascades the persist.

## Considered and not adopted

- **`KernelBrowser::disableReboot()`.** NTS measured a large further saving and rejected it. With a long-lived entity manager, entities that the app keeps in the session collide with managed copies, and services not tagged `kernel.reset` keep request state, so the harness stops matching production's per-request lifecycle. RFS keeps wizard state in the session too.
- **A JavaScript unit runner.** The frontend bundle already has JS tests for its own modules, and RFS's own JS is now limited to `session-reminder.js` and the admin modules.
- **ParaTest.** Possible now that no shared web server is involved, but `DATABASE_URL` (`sqlite:////tmp/test.sqlite`) would first need a `${TEST_TOKEN}` interpolation. Follow-up work.
- **Merging `tests/Functional` and `tests/NewFunctional`.** Both now run in-process, with two different frameworks. Worthwhile, but separate work.

## Things that will bite you

- Concurrent test runs share `/tmp/test.sqlite` and liip's fixture snapshots in `var/cache/test`, and corrupt each other silently, with symptoms that look like application bugs. Take timings from one run at a time, after deleting `.phpunit.cache/test-results`, which merges rather than prunes.
- If a Panther run is killed, its web server can be left holding port 9080, and the next `JavaScriptBehaviourTest` run fails with "The port 9080 is already in use".
- The test environment runs with `APP_DEBUG=false`, so after editing config or Twig templates, run `bin/console cache:clear --env=test` before relying on a test result.
