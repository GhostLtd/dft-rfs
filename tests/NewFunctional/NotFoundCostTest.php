<?php

namespace App\Tests\NewFunctional;

use App\Tests\Support\DatabaseActivityRecorder;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\EventListener\ProfilerListener;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * A request that doesn't match a route must not open a database connection or start the session (in prod
 * the session is PdoSessionHandler, so starting it is a DB hit). See docs/ADRs/2026-10-12-404s-without-database.md.
 *
 * Runs with debug off: with debug on, Symfony renders its own exception page rather than our error
 * templates, so a template that touches the session would go unnoticed.
 */
class NotFoundCostTest extends WebTestCase
{
    private KernelBrowser $client;

    /** @var array{initialised: bool, started: bool}|null */
    private ?array $sessionState = null;

    public static function notFoundProvider(): array
    {
        return [
            'frontend, top level' => ['FRONTEND_HOSTNAME', '/no-such-page'],
            'frontend, firewalled prefix' => ['FRONTEND_HOSTNAME', '/domestic-survey/no-such-page'],
            'admin, top level' => ['ADMIN_HOSTNAME', '/no-such-page'],
            'admin, section prefix' => ['ADMIN_HOSTNAME', '/csrgt/no-such-page'],
        ];
    }

    #[DataProvider('notFoundProvider')]
    public function testNotFoundDoesNotTouchTheDatabaseOrSession(string $hostnameEnv, string $path): void
    {
        $this->initialiseClient($_ENV[$hostnameEnv]);

        $this->recordDuring(fn() => $this->client->request('GET', $path . '-' . uniqid()));

        self::assertResponseStatusCodeSame(404);
        // Sanity: the branded error page rendered, not Symfony's debug page
        self::assertSelectorTextContains('h1', 'Page not found');

        self::assertSame(0, DatabaseActivityRecorder::getConnectionCount(), 'A 404 must not open a database connection');
        self::assertSame([], DatabaseActivityRecorder::getQueries(), 'A 404 must not run any database queries');

        self::assertNotNull($this->sessionState, 'Session observer did not run');
        self::assertFalse($this->sessionState['started'], 'A 404 must not start the session');
        // In prod, merely initialising the session constructs PdoSessionHandler, which opens a connection.
        // The test session handler isn't PDO-backed, so the connection count above can't catch that.
        self::assertFalse($this->sessionState['initialised'], 'A 404 must not initialise the session');
    }

    /**
     * Positive control: without this, a recorder that isn't wired in would make the zero-counts above pass.
     */
    public function testRecorderSeesRequestTimeDatabaseActivity(): void
    {
        $this->initialiseClient($_ENV['FRONTEND_HOSTNAME']);
        static::getContainer()->get(DatabaseToolCollection::class)->get()->loadFixtures([]);
        // Loading the fixtures connected; drop that so the request has to connect again
        static::getContainer()->get('doctrine.dbal.default_connection')->close();

        // /login isn't on the maintenance whitelist, so the maintenance lock is checked
        $this->recordDuring(fn() => $this->client->request('GET', '/login'));

        self::assertResponseIsSuccessful();
        self::assertSame(1, DatabaseActivityRecorder::getConnectionCount());
        self::assertNotEmpty(array_filter(
            DatabaseActivityRecorder::getQueries(),
            fn(string $sql) => str_contains($sql, 'maintenance_lock'),
        ));
    }

    private function initialiseClient(string $hostname): void
    {
        $this->client = static::createClient(['debug' => false]);

        // With debug off Twig doesn't auto-reload, so a stale compiled template could hide a template regression
        (new Filesystem())->remove(static::getContainer()->getParameter('kernel.cache_dir') . '/twig');

        // cookie_secure is on, and routes are host-matched
        $this->client->setServerParameter('HTTPS', 'on');
        $this->client->setServerParameter('HTTP_HOST', $hostname);

        $dispatcher = static::getContainer()->get('event_dispatcher');

        // The profiler's response listener calls $request->hasPreviousSession(), which initialises the
        // session. WebProfilerBundle is only registered for dev/test, so prod has no such listener.
        foreach ($dispatcher->getListeners() as $eventName => $listeners) {
            foreach ($listeners as $listener) {
                if (is_array($listener) && $listener[0] instanceof ProfilerListener) {
                    $dispatcher->removeListener($eventName, $listener);
                }
            }
        }

        // Before SessionListener (-1000) saves and closes the session. hasSession(true) is false while the
        // session is still an uninitialised factory; plain hasSession() + getSession() would initialise it.
        $dispatcher->addListener(KernelEvents::RESPONSE, function (ResponseEvent $event) {
            if ($event->isMainRequest()) {
                $request = $event->getRequest();
                $initialised = $request->hasSession(true);
                $this->sessionState = [
                    'initialised' => $initialised,
                    'started' => $initialised && $request->getSession()->isStarted(),
                ];
            }
        }, -999);
    }

    private function recordDuring(callable $callback): void
    {
        DatabaseActivityRecorder::start();
        try {
            $callback();
        } finally {
            DatabaseActivityRecorder::stop();
        }
    }

    #[\Override]
    protected function tearDown(): void
    {
        DatabaseActivityRecorder::reset();
        parent::tearDown();
    }
}
