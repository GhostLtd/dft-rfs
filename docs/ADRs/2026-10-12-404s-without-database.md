[Home](../../README.md) > ADR: 404s without the database

# Decision record for 404s without the database

Title: A request that doesn't match a route must not open a database connection or start the session

Status: implemented 2026-10-12.

This follows the same decision taken on NTS (`dft-nts` commit `530dfcba`, 2026-06-02), adapted to RFS.

## Context and problem statement

Unknown URLs, mostly automated probe traffic, are served by App Engine instances. Each request that reached Symfony and ended in a 404 also opened a database connection and ran statements against it. That is wasted cost, and it ties error pages to database availability.

NTS's fix had several parts. Where RFS stands on each:

| NTS change (`530dfcba`) | RFS |
|---|---|
| `app.yaml` handlers serve probe traffic as a static 404, so it never reaches PHP | Already done (`5f43e2be`). `config/gcloud-build/all/app.default.yaml` matches NTS |
| `MaintenanceLockSubscriber` only gates the main request | Needed: cause 1 below |
| Error pages don't render flash banners | Needed: cause 2 below. RFS renders the banners in a different block, so the override differs |
| `SecureHeadersSubscriber`: `csp:add` moved from the session flash bag to a request attribute | Not applicable. RFS's `SecureHeadersSubscriber` doesn't touch the session |
| Error pages drop the viewport-size monitor | Not applicable. RFS has no viewport monitor |
| `max_concurrent_requests` 50 → 30 | Not adopted here (see below) |
| `NotFoundCostTest` | Needed, in a stronger form (see Tests) |

### What a 404 does today (measured)

A throwaway probe test recorded every SQL statement, with its call stack, through a DBAL middleware. It also recorded a stack trace whenever the session started, through a session-storage factory. The probe requested unknown URLs (`/no-such-route-*`, `/domestic-survey/no-such-*`, `/roro/no-such-*`, `/admin/no-such-*`) on both the frontend and admin hosts, with `debug` on and off. Every case returned a 404, and every case hit the database:

| debug | DB queries | Session started |
|---|---|---|
| on | 1 (`SELECT … FROM maintenance_lock`) | no (Symfony's debug exception page) |
| off | 1 (`SELECT … FROM maintenance_lock`) | **yes** (branded error template) |

The test environment stores sessions with `mock_file`, so a started session there doesn't show up as a query. In prod the session handler is `PdoSessionHandler` with `LOCK_ADVISORY`. A started session there means an advisory lock, a session read, and a write or destroy at the end of the request.

#### Cause 1: `MaintenanceLockSubscriber` runs on the error sub-request

`MaintenanceLockSubscriber` listens on `kernel.request` at priority 20.

- On the main request, `RouterListener` (priority 32) throws `NotFoundHttpException` first, so the maintenance check never runs there.
- `ErrorListener` then renders the error page in a **sub-request**, which goes through `kernel.request` again. The sub-request's `_route` is null, so the route whitelist doesn't match, and `isMaintenanceMode()` calls `MaintenanceLockRepository::isLocked()`.

Recorded stack: `ErrorListener::onKernelException → … → MaintenanceLockSubscriber::onKernelRequest → isMaintenanceMode → MaintenanceLockRepository::isLocked`.

A side effect: during maintenance mode, an unknown URL currently returns the **maintenance page body with a 404 status**. The sub-request's 200 response gets the exception's status code.

#### Cause 2: the error page reads the flash bag, which starts the session

`error-base.html.twig` extends `base.html.twig`. The `main` block in `base.html.twig` unconditionally calls `{{ flashNotificationBanners() }}`.

Recorded stack: `macro_flashNotificationBanners → AppVariable::getFlashes → Session::getFlashBag → storage start()`.

`error-base` already empties `sessionWarning`, but leaves the flash banners alone.

#### Checked, and not a cause

- **Firewalls.** `main` and `roro` are `lazy`, and `admin` is `stateless`. On a route 404, `Firewall` (`kernel.request` priority 8) never runs on the main request because `RouterListener` has already thrown. It also skips sub-requests. So the session is never read for authentication, and remember-me never runs.
- **`App\Logger\SessionProcessor` / `UserProcessor`** (used by `RequestSummarySubscriber` on every request). They only read the cookie and the token storage.
- **The `csrgt_quality_checks` Twig global** (`QualityCheckEngine`). Its checks are a lazy iterable, and its constructor does no I/O.
- **`is_granted()` in the `header` block.** It returns false when there is no token.
- **`templates/admin/base-with-nav.html.twig`** also renders flash banners, but error pages don't extend it.

## Decision

1. **`MaintenanceLockSubscriber` only gates the main request**, as on NTS:

   ```php
   // Only gate the main request. Without this the maintenance DB check (isLocked()) runs on the
   // error sub-request used to render 404 pages (and on any other sub-request).
   if (!$event->isMainRequest()) {
       return;
   }
   ```

2. **Error pages don't render flash banners.** `base.html.twig` wraps the banners in a `flashBanners` block. The macro import moves *inside* that block: a `{% from %}` import is scoped to the block it appears in, and a nested block can't see an import made in its parent (`Unknown "flashNotificationBanners" function`). `errorSummary` stays imported in `main`.

   ```twig
   {% block flashBanners -%}
       {%- from '@GhostGovUkFrontend/components/macros.html.twig' import flashNotificationBanners -%}
       {{ flashNotificationBanners() }}
   {%- endblock %}

   {%- from '@GhostGovUkFrontend/components/macros.html.twig' import errorSummary -%}
   ```

   `error-base.html.twig` empties the block, next to the existing empty `sessionWarning`:

   ```twig
   {# Don't render flash banners on error pages: reading the flash bag starts the session, which is a
      PdoSessionHandler DB hit (incl. on every 404). A queued flash just shows on the next normal page. #}
   {% block flashBanners %}{% endblock %}
   ```

   The error summary stays. It only filters `_context` and doesn't touch the session.

3. **A regression test that runs the prod rendering path and counts connections as well as queries** (see Tests).

## Tests

NTS's `NotFoundCostTest` counts queries with the Doctrine profiler, which only works with `debug=true`. With debug on, Symfony renders its own exception page instead of the branded template, so that test can't catch cause 2. RFS's test runs with `debug=false` and uses a DBAL middleware instead.

- **`tests/Support/DatabaseActivityRecorder.php`**: a `Doctrine\DBAL\Driver\Middleware` that wraps the driver and connection.
  - While recording, it captures every driver `connect()` call, so a connection opened without running SQL still counts. It also captures every `prepare` / `query` / `exec` SQL string.
  - It does nothing when recording is off, so it's registered for the whole test environment in `config/services_test.yaml` (tag `doctrine.middleware`). The entry is explicit because services in `tests/` aren't auto-registered.
  - It has `start()` / `stop()` / `reset()`. Callers stop and reset it in a `finally`, so a failing assertion can't leak state into the next test.
- **`tests/NewFunctional/NotFoundCostTest.php`** (BrowserKit, in-process):
  - `static::createClient(['debug' => false])`. Remove `var/cache/test/twig` before booting: with debug off, Twig's `auto_reload` is off, and a stale compiled template could hide a template regression. NTS does the same.
  - Data provider: frontend and admin hosts, each with a top-level path and a path under a firewalled prefix (e.g. `/domestic-survey/…`). Set `HTTPS=on` and `HTTP_HOST`, and request `/<path>-<uniqid>` with recording on.
  - Assert:
    - the status is 404, and the page is the branded one (e.g. contains `govuk-`), so a debug-page fallback can't pass silently;
    - **no connections** were opened and no queries were recorded;
    - **the session wasn't started.** A `kernel.response` listener at priority -999, which runs before `SessionListener` saves and closes the session at -1000, captures `$request->hasSession(true) && $request->getSession()->isStarted()` for the main request.

      It must be `hasSession(true)`. Plain `hasSession()` is also true while the session is still an uninitialised factory, and the `getSession()` call that follows would then initialise it. In prod, initialising the session builds `PdoSessionHandler`, whose PDO argument comes from `database_connection->getNativeConnection()`, so that alone opens a connection.
  - Assert as well that **the session was never initialised** (`hasSession(true) === false`). This is closer to prod: the test handler is `native_file`, not PDO, so the connection count can't catch an initialisation that would connect in prod.

    The test env has one extra session initialiser, `ProfilerListener`, which calls `$request->hasPreviousSession()` on every response. WebProfilerBundle is only registered for dev/test, so prod doesn't have it. The test removes that listener from the dispatcher. With it gone, a 404 never initialises the session.
  - **Positive control** (`testRecorderSeesRequestTimeDatabaseActivity`): load the empty schema, close the connection, and request `/login`, which isn't on the maintenance whitelist. Assert that one `connect()` and the `maintenance_lock` query were recorded. Otherwise a recorder that was never wired in would make the zero-assertions pass vacuously.
  - Fixtures: the 404 cases load none, because a 404 needs no schema.
- **`tests/Functional/MaintenanceLockTest.php`**: with `MaintenanceLockFixtures` loaded, an unknown URL returns a 404, not the maintenance page (`testMaintenanceLockActiveUnknownUrlIsNotFound`). This test is required, because it pins down the behaviour change below.

### Verifying the tests test the right things

1. Write the tests before the fix. The positive control should pass. `NotFoundCostTest` should fail for **both** reasons: a connection and a query are recorded, and the session is started.
2. Apply decision 1 alone. The connection and query assertions should pass and the session assertion should still fail. Then apply decision 2, and everything should pass.
3. Check that flash banners still render on normal pages (e.g. the `MaintenanceWarningBannerTest` cases).
4. Run `MaintenanceLockTest`, `MaintenanceWarningBannerTest`, `SessionTimeoutTest`, then the full suite and PHPStan.
5. Optional: on the dev deployment, request an unknown URL and confirm in Cloud SQL Query Insights or the logs that no `maintenance_lock` or `sessions` statements come from it.

## Consequences

- **Maintenance mode.** Unknown URLs show the normal 404 page instead of the maintenance page body with a 404 status. That is the intended behaviour, and the `MaintenanceLockTest` case above enforces it. Known routes are unaffected.
- **Flash banners on error pages.** A flash queued earlier in a request that then fails isn't shown on the error page; it appears on the next normal page instead. For example, `MaintenanceWarningBannerSubscriber` adds its banner at `controller_arguments`, and then the controller throws a 403 or 404. `maintenance.html.twig` extends `error-base`, so it loses the banners too.
- **What this doesn't cover.** 404s thrown *inside* controllers (`#[MapEntity]` not found, `IsGranted(statusCode: 404)`) hit the database before they are thrown; that is inherent. The error page no longer adds a session start on top of them, though. 405s and other `RouterListener` exceptions take the same path as route 404s, so they are covered.

## Considered and not adopted

- **Showing banners only when `app.request.hasPreviousSession` is true.** This would also avoid a session start on normal anonymous pages. But `MaintenanceWarningBannerSubscriber` adds a flash in the same request for first-time visitors (the landing page), and that banner would disappear.
- **`max_concurrent_requests` 50 → 30.** NTS sized that number from its own memory measurements, so RFS would need its own before changing it. It belongs in a separate ticket.
- **Probe handlers in `app.admin.yaml`.** The admin service sits behind IAP, so unauthenticated probes shouldn't reach PHP.

## Things that will bite you

- Anything that runs on `kernel.request` also runs on the error sub-request unless it checks `isMainRequest()`. Its `_route` is null, so route whitelists don't match.
- Anything an error template renders that reads the session (flashes, `app.session`, `app.flashes`) starts it. In prod, even calling `getSession()` without reading anything opens a connection, through `PdoSessionHandler`.
- A test with `debug=true` never renders the branded error templates.
- `$request->hasPreviousSession()` and plain `hasSession()` followed by `getSession()` both *initialise* the session, even if nothing is read from it. To look without touching it, use `hasSession(true)`.
