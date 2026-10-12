<?php

namespace App\Tests\NewFunctional;

use Doctrine\Common\DataFixtures\ReferenceRepository;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Base class for functional tests, which drive the app in-process through BrowserKit.
 *
 * Only tests that genuinely depend on JavaScript or real rendering need a browser - those live
 * in JavaScriptBehaviourTest, on AbstractPantherTestCase. See docs/BrowserKitFunctionalTestsPlan.md.
 */
class AbstractFunctionalTestCase extends WebTestCase
{
    use FunctionalHelpersTrait;

    protected KernelBrowser $client;

    protected ReferenceRepository $fixtureReferenceRepository;

    /**
     * @param array $serverOptions 'hostname' => the host to address requests to (routes are host-matched),
     *                             'env' => extra server parameters for every request (e.g. TEST_GOOGLE_IAP_AUTH_USER).
     *                             The same shape as AbstractPantherTestCase's, which gives these to its web server.
     */
    public function initialiseClientAndLoadFixtures(array $fixtures, array $serverOptions = []): void
    {
        // createClient() refuses to boot a second kernel, and several setUp()s fetch services (and so boot
        // the kernel) before getting here
        static::ensureKernelShutdown();

        // The client comes first: the database tool must come from the client's kernel
        $this->client = static::createClient();

        // Path assertions are written against the post-redirect URL
        $this->client->followRedirects();

        // cookie_secure is on, so without HTTPS the cookie jar would drop the session cookie
        $this->client->setServerParameter('HTTPS', 'on');

        if (isset($serverOptions['hostname'])) {
            $this->client->setServerParameter('HTTP_HOST', $serverOptions['hostname']);
        }

        foreach ($serverOptions['env'] ?? [] as $name => $value) {
            $this->client->setServerParameter($name, $value);
        }

        $databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $this->fixtureReferenceRepository = $databaseTool->loadFixtures($fixtures)->getReferenceRepository();
    }

    #[\Override]
    public function getCurrentUri(): string
    {
        return $this->client->getInternalRequest()->getUri();
    }
}
