<?php

namespace App\Tests\Config;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * App Engine serves /build itself (a static_dir handler), so Nelmio's security headers never reach those files: the
 * handler must set them. Checked from the config, since only App Engine applies it (found by DAST: ZAP rules 10035,
 * 10021). The HSTS value must match Nelmio's, which the pages send.
 */
class AppEngineStaticHeadersTest extends TestCase
{
    /** The frontend (default) and admin services each have their own config */
    public static function appConfigs(): array
    {
        return [['app.default.yaml'], ['app.admin.yaml']];
    }

    #[DataProvider('appConfigs')]
    public function testBuildFilesGetTheSecurityHeaders(string $appConfigFile): void
    {
        $projectDir = dirname(__DIR__, 2);
        $appConfig = Yaml::parseFile("{$projectDir}/config/gcloud-build/all/{$appConfigFile}");
        $build = array_values(array_filter($appConfig['handlers'], fn(array $handler) => ($handler['url'] ?? null) === '/build'));
        self::assertCount(1, $build, 'One /build handler');
        $headers = $build[0]['http_headers'] ?? [];

        $forcedSsl = Yaml::parseFile("{$projectDir}/config/packages/nelmio_security.yaml")['nelmio_security']['forced-ssl'];
        $hsts = "max-age={$forcedSsl['hsts_max_age']}" . (($forcedSsl['hsts_subdomains'] ?? false) ? '; includeSubDomains' : '');

        self::assertSame($hsts, $headers['Strict-Transport-Security'] ?? null, "HSTS as Nelmio sends it for pages");
        self::assertSame('nosniff', $headers['X-Content-Type-Options'] ?? null);
    }
}
