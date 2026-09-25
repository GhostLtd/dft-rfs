<?php

namespace App\Tests\Utility\AlphagovNotify;

use App\Entity\Domestic\Survey as DomesticSurvey;
use App\Entity\PasscodeUser;
use App\Utility\AlphagovNotify\PersonalisationHelper;
use App\Utility\LoginLinkToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Routing\RequestContext;

class PersonalisationHelperLoginLinkTest extends KernelTestCase
{
    private static function survey(string $code1): DomesticSurvey
    {
        return (new DomesticSurvey())
            ->setPasscodeUser((new PasscodeUser())->setUsername($code1))
            ->setRegistrationMark('AB12CDE')
            ->setSurveyPeriodStart(new \DateTime('2026-09-07'))
            ->setSurveyPeriodEnd(new \DateTime('2026-09-13'));
    }

    /** The reminders run from the CLI (rehearsal), from HTTP cron routes (exempt from forced SSL) and from the admin site. */
    public static function contexts(): iterable
    {
        yield 'CLI' => [new RequestContext()];
        yield 'HTTP cron' => [new RequestContext('', 'GET', 'rfs-app.appspot.com', 'http', 80, 443, '/cron/reminders/process-in-progress')];
        yield 'HTTPS admin' => [new RequestContext('', 'GET', $_ENV['ADMIN_HOSTNAME'] ?? 'rfs-admin.localhost', 'https', 80, 443, '/csrgt/surveys')];
    }

    #[DataProvider('contexts')]
    public function testLoginLinkIsAnHttpsShortLinkOnTheFrontEndHost(RequestContext $context): void
    {
        $container = static::getContainer();
        $container->get('router')->setContext($context);

        $link = $container->get(PersonalisationHelper::class)->getForEntity(self::survey('12345678'))['loginLink'];

        $frontend = $container->getParameter('frontend_hostname');
        self::assertSame("https://{$frontend}/c/0pnfq", $link);
        self::assertSame('12345678', LoginLinkToken::decode(basename($link)));
    }
}
