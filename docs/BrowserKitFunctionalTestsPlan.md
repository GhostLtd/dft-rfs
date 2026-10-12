# Plan: move the Panther functional tests onto BrowserKit

Status: **implemented** 2026-10-08 (Codex-reviewed). Outcome and verification are recorded in [the ADR](ADRs/browserkit-first-functional-tests/index.md).

## Summary

All 101 Panther tests (`tests/NewFunctional`) can run in-process on Symfony's `KernelBrowser` (BrowserKit / `WebTestCase`) without losing any assertion they make today. None of them assert anything that needs JavaScript or real rendering. The browser costs about 53% of the suite's wall clock and causes the navigation race that `runAndWaitForNavigation()` works around.

This follows the conversion already done on NTS (squashed as `de078771` on `origin/2026-09-tom-journey-view` in `dft-nts`; see `docs/ADRs/browserkit-first-functional-tests/index.md` on that branch). RFS should be an easier conversion than NTS was:

- RFS already has a working in-process functional suite (`tests/Functional`, 219 tests on `WebTestCase`), with host-based routing, IAP admin login, passcode login and liip's SQLite `cache_db` already solved.
- NTS needed a browser for 4 tests. In RFS no test currently needs one (see "Audit").

## Current state (measured)

From `.phpunit.cache/test-results` (last full run, 2026-10-06):

| Group | Tests | Time | Avg/test | Share |
|---|---|---|---|---|
| `tests/NewFunctional` (Panther) | 101 | 248.3s | 2.458s | **53%** |
| `tests/Functional` (KernelBrowser) | 219 | 145.8s | 0.666s | 31% |
| Everything else | 2211 | 70.2s | 0.032s | 15% |
| **Total** | | **464.2s** | | |

Caveats:
- These are summed per-test times. Bootstrap time and the one-off chromedriver/Chrome start-up (which happens when the first Panther client of a run is created) are not included.
- The result cache merges entries and never prunes them. Delete it before taking the "before" and "after" baselines.

The heaviest Panther classes are International/Domestic `ClosingDetailsTest` (64s together), the two `TripTest`s (48s) and `PreEnquiryChangeTest` (13.5s).

**Expected outcome.** On NTS the converted tests went from about 3.2s to about 1.3s each, a 2.5x speedup. The limit is that `KernelBrowser` reboots the kernel on every request. Applying the same ratio here gives roughly 248s → 100s for the converted group, so the whole suite goes from about 464s to about 315s (around 30% faster). This is an estimate; we should measure the real figure once the work is done.

## Audit: what is the browser actually buying us?

I searched `tests/NewFunctional` for anything that depends on JavaScript or rendering: `executeScript`, `isDisplayed`, `*Visible*`, `waitFor*`, `back()`, `reload()`, `sleep` and WebDriver element APIs.

- **No assertions depend on JavaScript having run.** There are only two `executeScript` calls, both in `AbstractFormTestAction`. They save and restore form state between form test cases (test plumbing, not something under test).
- **Every WebDriver API call is page inspection** that DomCrawler can do just as well:
  - `findElement(s)` + `getText()` / `getAttribute()` in `AbstractFunctionalTestCase` helpers (`getHeading`, `getTableData`, `getTableCaption`, `getElementByTextContains`, breadcrumbs/back-link helpers)
  - `AbstractFormTestAction::getErrors()` and the "describe the form" error path
  - `PantherBugWorkaround`
  - 4 test-level call sites: `OperatorGroupWizardTest:73`, `VehicleTest:29`, `DvlaBulkImportTest:148,169`
- **`time_sleep_until()` in `AbstractRoRoTestCase::setUp()`** works around Symfony login-link generation having 1-second resolution. It has nothing to do with the browser, so keep it.
- **No test exercises our own JS modules.** That covers `assets/js/session-reminder.js`, `radios.js` (`data-aria-hide-controls`), `double-submit-prevention.js` and the admin modules (`lcni-lookup`, `email-blacklist-check`, `irhs-trip`, `blame-log`, `dashboard`). Panther loads the bundle on every page, but nothing asserts that it ran. The only coverage this gives is incidental: if JavaScript hid a field a test tried to fill, WebDriver would throw "element not interactable".
- The server side of the admin AJAX endpoints already has coverage (e.g. `tests/Controller/Admin/LcniLookupControllerTest.php`), and moving off Panther doesn't touch that.

Conclusion: converting everything loses no assertion. All we lose is the incidental signal that the JS bundle loads, and D1 replaces that with a deliberate check.

## What the browser is *hiding* (expected wins)

1. **Status codes.** `AbstractFormTestAction` currently has no error check at all. Unlike NTS, it doesn't even scrape the `h1.exception-message`. A form submission that 500s and renders the error page only fails if a later path/error assertion happens to trip. `KernelBrowser` exposes the real `Response`, so we can add `assertSubmissionDidNotError()` as `assertLessThan(500, $status)`. NTS's own diff explains why this must be a counted assertion rather than a conditional `fail()`. **This may surface real bugs**, so budget time to triage them.
2. **Request-scoped state held in statics.** Panther's PHP built-in server re-executes the entry script for every request, so function-statics reset each time. `KernelBrowser` shares the process with the test, so they don't. On NTS this found three real bugs. In RFS I found one candidate:
   - `src/Repository/AuditLog/AuditLogRepository.php:75`: `static $localCache = []` in `getLogs()`. In PHP 8.1+, method statics are shared across every instance of the class, so this cache outlives the request and the entity manager that produced its entities. It's latent in production (App Engine is per-request), but it would bite on any long-lived runtime (messenger consumers, FrankenPHP/RoadRunner). The fix is an instance property. `tests/Functional` admin tests may already reach this code, so check whether any of them are working around it.
3. **The navigation race goes away.** `runAndWaitForNavigation()` exists only because Panther's Chrome `click()`/`submit()` return before navigation finishes. BrowserKit is synchronous, so the helper, its stale-element/ChromeDriver-121 workaround, and the rule that "every new direct navigation call must go through it" can all be removed.

## Plan

The approach is the same as NTS. First make the shared framework client-agnostic while it still runs on Panther, then switch the base class, then delete the dead code. Each phase should leave the suite green.

### Phase 0: baseline

- Delete `.phpunit.cache/test-results`. Run the full suite once on its own (no concurrent runs, since they share `/tmp/test.sqlite`), with `--log-junit var/junit-before.xml`.
- Record per-group timings in the table format above.

### Phase 1: make the helpers client-agnostic (still on Panther)

`Symfony\Component\Panther\Client` extends BrowserKit's `AbstractBrowser`, and Panther's `Crawler` extends DomCrawler's `Crawler`. Anything written against those supertypes therefore works on both clients.

1. Split `tests/NewFunctional/AbstractFunctionalTestCase.php`:
   - Move the page-inspection and assertion helpers into a `FunctionalHelpersTrait`, written against `AbstractBrowser` + DomCrawler: `assertPath*`, `getHeading`, `getTableData`, `getSummaryListData`, `getTableCaption`, `clickLinkContaining`, `assertDataSize`, `assertDataHasKeyValuePair`, `assertTime/DateEquals`, `assertSummaryListData`, `assertSummaryData`, `assertDataMatches`, and the breadcrumb/back-link helpers.
   - Add `abstract getCurrentUri()`. Panther provides it via `getCurrentURL()`; `KernelBrowser` via `getInternalRequest()->getUri()`.
   - **`getTableData()` gotcha (found on NTS):** DomCrawler rewrites a leading `./` to `self::`, so `filterXPath('./*')` returns the row rather than its cells. Use `->children()` instead.
2. Rewrite the four test-level WebDriver call sites (`OperatorGroupWizardTest`, `VehicleTest`, `DvlaBulkImportTest` ×2) as `$client->getCrawler()->filter(...)->text()`.
3. Change `AbstractRoRoTestCase`'s `use Symfony\Component\Panther\DomCrawler\Crawler` to `Symfony\Component\DomCrawler\Crawler`. It is the type of the `reduce()` callback parameter in `clickSummaryRowLink()`, so once the client switches, `SurveyWizardTest` throws a `TypeError`. (Reproduced in review.) Grep `tests/NewFunctional` for any other `Panther\` imports at the same time. The exceptions are `Context` and the form framework (`AbstractFormTestAction`, `PantherBugWorkaround`), which change together in Phase 2.
4. Check: `docker exec ... php vendor/bin/phpstan analyse tests/NewFunctional` at the configured level (5, from `phpstan.neon`), which already reports leftover Panther-only calls (`getText`, `getAttribute`, `findElement`, `getPageSource`) as `method.notFound`. Don't pass `--level=2`: on RFS that would *lower* the level. The NTS advice was relative to NTS's level 1. Expect the form framework (`AbstractFormTestAction`, `PantherBugWorkaround`) to stay Panther-typed at this point. `Context` isn't retyped until Phase 2, which is what keeps this gate passing.

### Phase 2: add the BrowserKit base and switch over

1. Add `AbstractFunctionalBrowserTestCase extends WebTestCase` with the same `initialiseClientAndLoadFixtures(array $fixtures, array $pantherOptions = [])` signature:
   - Call `static::ensureKernelShutdown()` first. Some tests call it twice, and several `setUp()`s boot the kernel before it (`AbstractPasscodeWizardTestCase` fetches `PasscodeGenerator`; `AbstractAdminTestCase` and `AbstractRoRoTestCase` fetch the entity manager). `createClient()` refuses to run when a kernel is already booted.
   - Create the client **before** loading fixtures. The database tool must come from the client's kernel.
   - Call `followRedirects(true)`. Panther follows redirects implicitly, and every path assertion is written against the URL after the redirect.
   - Map `$pantherOptions` onto server parameters so the subclass call sites don't change:
     - `hostname` → `setServerParameter('HTTP_HOST', …)`. Routes are host-matched (`config/routes/attributes.yaml`: `%frontend_hostname%` / `%admin_hostname%`).
     - **Always set `HTTPS => 'on'`** at client creation. `config/packages/framework.yaml` sets `cookie_secure: true`. Without HTTPS, relative requests go over http, BrowserKit's cookie jar drops the secure session cookie, and login and wizard state are lost. (Confirmed in review against the installed BrowserKit. This is also why `tests/Functional` requests `https://…` URLs.)
     - `env['TEST_GOOGLE_IAP_AUTH_USER']` → `setServerParameter('TEST_GOOGLE_IAP_AUTH_USER', …)`. `App\Security\GoogleIap\IapAuthenticator` reads it from `$request->server`, so this works in-process as-is. Update the authenticator's "used by Panther tests" comment.
   - Load fixtures through the same liip database tool. `cache_db` is already enabled in `config/packages/test/liip.yaml`.
2. Retype `Context::$client` and `getClient()` as `AbstractBrowser`. This has to happen in the same step as item 3, not in Phase 1: until then `getBrowserSideSavedFormData()`, `restoreFormData()` and `PantherBugWorkaround` still require Panther's `Client`, and passing them an `AbstractBrowser` breaks PHPStan.
3. Port the form-rewind logic in `AbstractFormTestAction` from NTS:
   - **Rewinding.** Every form test case after the first must be submitted against the form *as originally rendered*. Re-requesting the page is not equivalent, because the wizard persists partly-valid data into session state even when validation fails, so a "required" assertion would pass silently. Panther handles this with the `executeScript` save/restore. Off-browser, save `[$response->getContent(), $request->getUri()]` once and build a fresh `new Crawler($html, $uri)` for each test case after the first. This costs no round trip. It has to be a fresh parse because a DomCrawler `Form` writes back into the nodes it was built from.
   - **Checkboxes and radios.** Replace `PantherBugWorkaround` with NTS's `applyChoiceSelections()`, which sets or removes `checked` directly on the DOM nodes. DomCrawler treats each checkbox of a `name[]` group as a separate field ("Unreachable field"), and rejects `''` for a choice field, while RFS form data uses `''`/`false`/`null` to mean "leave unselected".
   - **Status check.** Add `assertSubmissionDidNotError()` as a counted `assertLessThan(500, …)`, including the exception message in the failure text when there is one.
   - **Error message.** When "Page path did not change when expected" fires and no errors were expected, include the validation errors found on the page in the message (NTS did the same).
   - **File uploads.** `DvlaBulkImportTest` passes a filesystem path for a file field. DomCrawler's `FileFormField` accepts a path via `setValues`, so this should work unchanged; verify it.
4. Point `AbstractWizardTestCase` (and therefore everything under `tests/NewFunctional`) at the new base.
5. Make `runAndWaitForNavigation()` a plain `$action()` for now. Inline and delete it in Phase 3.

**Things expected to break on the first run, with the likely fix for each:**

| Symptom | Cause | Fix |
|---|---|---|
| Summary-list values differ only in whitespace (`"Yes\n\nRigid…"`, PreEnquiry address `join(",\n", …)`) | WebDriver `getText()` returns *rendered* text (`<br>`/block → newline). DomCrawler `text()` collapses whitespace. | Per D2: compare normalised text in the 3 expectations (`International/AbstractTripTestCase:105`, `TripDistanceDisabled/AbstractTripTestCase:100`, `PreEnquiry/AbstractPreEnquiryTestCase:33`). Where a line break matters (e.g. address lines), add an explicit assertion that the `<br>` is in the cell's markup. |
| Extra text appearing in a cell | `getText()` skips non-displayed elements. DomCrawler reads all markup, hidden or not. | Narrow the selector. (This is the inherent gap: off-browser tests can't tell shown content from hidden content.) |
| `refresh()` fails / detached entity | `KernelBrowser` reboots the kernel between requests, which clears the entity manager | `clear()` + `find()`. Also consider having `Context::getEntityManager()` resolve from `static::getContainer()` lazily rather than holding the first container's EM. (`CallbackAction` already `clear()`s before each callback, which helps.) |
| HTTP 500 caught by the new status check | A real bug the browser was hiding | Triage individually. Fix in `src/` if it's genuine. |
| Leaked state between tests | function-static / static caches now outlive the request | e.g. `AuditLogRepository::getLogs()`. Fix in `src/` (instance property). |

### Phase 3: delete the dead Panther code

- `PantherBugWorkaround`, `getBrowserSideSavedFormData()`/`restoreFormData()`, the `NoSuchElementException` branch (narrow the catch to `InvalidArgumentException`), and `runAndWaitForNavigation()` with all its call sites.
- The wizard framework's debug screenshot machinery (`takeDebugScreenshot`). With nothing left to take screenshots, `DEBUG` levels 3–5 become no-ops; levels 1–2 (action headers, form data, expected errors) stay. Keep the `takeScreenshotIfTestFailed` override, but on `AbstractPantherTestCase` only, for the one remaining browser class.
- The logout-in-`tearDown()` in `AbstractPasscodeWizardTestCase` and the logout in `AbstractRoRoTestCase::setUp()`. These existed because Panther's client and session outlived each test. In-process every test gets a fresh client and cookie jar. They're harmless, so this is optional.
- Rename the remaining Panther-flavoured names so they say what they are (e.g. drop the `$pantherOptions` parameter name once it's only ever host/server params).

### Phase 4: keep one browser class (D1)

Add a single `JavaScriptBehaviourTest` on a slimmed-down `AbstractPantherTestCase` (only starting the browser and clicking live elements; the helpers come from `FunctionalHelpersTrait`). It covers:
- **Bundle smoke test.** Assert something only the compiled bundle produces. "The conditional reveal is collapsed after load" is **not** enough, because it would pass with the JavaScript broken: the theme renders reveals with `__conditional--hidden` already set (`vendor/ghost/govuk-frontend-bundle/templates/theme.html.twig:365`), and an inline script in `template.html.twig:33` adds `js-enabled` (and `govuk-frontend-supported`) without the bundle. Instead, on a page with a conditional reveal:
  1. assert the controlling radio has `aria-expanded="false"`, an attribute that govuk-frontend's Radios JavaScript adds and the template doesn't render;
  2. click the radio;
  3. assert `aria-expanded="true"` and that the reveal has lost `__conditional--hidden`.

  This proves `initAll()` ran and the behaviour works. A broken import or syntax error anywhere in `common.js`/`app.js` must fail it. When a wait times out, fail with a message naming `yarn build`, since a stale `public/build` is the likeliest cause.
- **`radios.js` `data-aria-hide-controls`.** Checking the controlling radio hides its targets, and unchecking shows them again.

It has to stay **one class**. Panther applies web-server options on the first `createPantherClient()` of a run and keeps that server, so a second Panther class would silently inherit the first one's configuration. Every test in the class passes the same options, so test order doesn't matter.

Keep `symfony/panther`, `dbrekelmans/bdi`, the `ServerExtension` entry and `PANTHER_*`. These cost nothing on runs that don't use a Panther client (see D1). Document the `yarn build` prerequisite next to the other test-suite notes.

### Phase 5: verify the tests still test the same things

This is the step that makes the conversion trustworthy rather than merely green. Do as NTS did:

1. **Inventory.** No test method deleted (compare the lists of test names before and after).
2. **Assertion counts.** Run with `--log-junit var/junit-after.xml`. Match tests on class + method + data set, and check that every test that passed before still passes. A plain "none has fewer assertions" check isn't enough: ten new status assertions could hide ten lost existing ones. So for each test, compare `after − (number of form submissions, i.e. new status assertions) − (any other intentional additions, e.g. D2's `<br>` assertions)` against `before`, and require it to be **equal**. Count submissions while the suite runs (e.g. a counter in `assertSubmissionDidNotError()` written out per test) rather than inferring them. Any shortfall means a loop now matches nothing, e.g. an XPath that DomCrawler evaluates differently. Any surplus is unexplained and needs looking at too.
3. **Deliberate bugs.** Apply each of these on its own, run the affected tests, confirm they fail, then revert. Suggested set:
   - Remove the validation behind `#early_response_is_correct` → `Domestic/ClosingDetailsTest`
   - Make `ReissueSurvey` not persist the new survey → `Admin/Domestic/ReissueSurveyTest` (`assertEntityCountInDatabase(Survey::class, 2)`)
   - Render the international trip summary without the trailer row → both `TripTest`s / `TripChangeTest`s
   - Break the vehicle-added notification banner → `International/VehicleTest`
   - Make the RoRo login-link handler reject valid links → `RoRo/LoginTest`, `RoRo/SurveyWizardTest`
   - Disable the bundle (comment out `gds.initAll()` in `assets/js/common.js`, or break an import) and run `yarn build` → `JavaScriptBehaviourTest`'s bundle smoke test and `radios.js` test both fail. Rebuild after reverting.
   - Throw an exception in a wizard step's submit handler → must be caught by the new `assertSubmissionDidNotError()` (proves the status check works)
4. **Timings.** Clear the result cache, run once on its own, fill in an "after" table next to Phase 0's.
5. PHPStan clean at the configured level (5), with no Panther-only calls reported under `tests/NewFunctional`.

### Phase 6: docs and memory

- Add a short ADR (`docs/` has no ADR folder yet; NTS uses `docs/ADRs/<slug>/index.md`) with the measurements, the audit and what was rejected (below).
- Update the "functional test flakiness" project memory: the `runAndWaitForNavigation` rule no longer applies.

## Decisions (agreed in review)

**D1: Keep one browser class** (Phase 4), with the bundle smoke test and the `radios.js` hide-controls check. Nothing tests our JS today, so this adds coverage rather than keeping any. NTS found that without a smoke test a broken import leaves every progressive enhancement dead behind a green suite. `session-reminder.js` is a candidate for later.
This doesn't slow down filtered runs. The installed Panther `ServerExtension`'s start-up hook (`ServerExtensionLegacy::executeBeforeFirstTest()`) only sets `$enabled` and keeps the server alive on teardown. Chrome/chromedriver and the web server start only when a test creates a Panther client, so a unit-only or `--filter` run that skips `JavaScriptBehaviourTest` never starts a browser. (This corrects an earlier draft, which repeated a claim from the NTS ADR without checking it.)

**D2: Compare normalised text,** with explicit `<br>` assertions wherever a line break matters (see the Phase 2 table). No `<br>`-aware text helper.

**D3: Defer consolidation** of `tests/Functional` and `tests/NewFunctional`. After this both trees are BrowserKit, with two different frameworks and two `AbstractFunctionalTestCase`s. Merging them is worthwhile but separate work.

## Considered and not proposed

- **`KernelBrowser::disableReboot()`.** NTS measured a further 57% saving and rejected it. With a long-lived entity manager, entities that the app stores in the session collide with managed copies, and services not tagged `kernel.reset` keep request state. In both cases the harness stops matching production's per-request lifecycle. RFS also keeps wizard state in the session, so the same reasoning applies. `tests/Functional/AbstractFunctionalTestCase::setUp()` already has `disableReboot()` commented out, which suggests someone tried it before.
- **A JavaScript unit runner (Vitest/jsdom).** NTS evaluated and rejected this. The frontend bundle already has JS tests for its own modules (`vendor/ghost/govuk-frontend-bundle/tests/js/radios.test.js`). An aside for a separate change: RFS's `assets/js/radios.js` looks like a copy of the bundle's `assets/js/radios.js`, so it may be removable (NTS dropped a similar duplicate, `show-password.js`).
- **ParaTest.** Panther pins a single web server, so parallelism wasn't possible before. In-process it becomes possible, but `DATABASE_URL` (`sqlite:////tmp/test.sqlite`) would first need a `${TEST_TOKEN}` interpolation, as NTS did. That's a follow-up, not part of this change.

## Risks

- **Fidelity gap.** Off-browser tests can't distinguish visible from hidden content. Today that is irrelevant (no visibility assertions), but it should be stated so that nobody writes one expecting it to work.
- **Concurrent runs** share `/tmp/test.sqlite` and `var/cache/test` fixture snapshots, and they corrupt each other silently, with symptoms that look like app bugs. Take every measurement from one run at a time.
- **`MessageHandlerTest` hits real Notify.** Exclude it from any full-suite runs here (`--exclude-filter MessageHandlerTest`).
