<?php

namespace App\Tests\Utility;

use App\Utility\Url;
use App\Utility\UrlSigner;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class UrlSignerTest extends WebTestCase
{
    protected UrlSigner $urlSigner;
    
    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();
        $this->urlSigner = static::getContainer()->get(UrlSigner::class);
    }
    
    public function dataValidFor(): array
    {
        $url = 'https://example.com/wibble?wobble=12&wubble=toast';

        return [
            'validFor = 1000: Test time equals signature time' => [$url, '1644500418', 1000, '1644500418', true],
            'validFor = 1000: Test time within limits' => [$url, '1644500418', 1000, '1644500555', true],
            'validFor = 1000: Test time at limit' => [$url, '1644500418', 1000, '1644501417', true],
            'validFor = 1000: Test time exceeds limit' => [$url, '1644500418', 1000, '1644501418', false],
            'validFor = 1000: Test time before signature time' => [$url, '1644500418', 1000, '1644500417', true],

            'validFor = 0: Test time equals signature time' => [$url, '1644500418', 0, '1644500418', true],
            'validFor = 0: Test time before signature time' => [$url, '1644500418', 0, '1644400418', true],
            'validFor = 0: Test time after signature time' => [$url, '1644500418', 0, '1644600418', true],
            'validFor = 0: Test time far past' => [$url, '1644500418', 0, '0', true],
            'validFor = 0: Test time far future' => [$url, '1644500418', 0, '9999999999', true],
            'validFor = 0: Replay test' => [$url, '1644500418', 0, '0', true], // There is no replay prevention for unlimited signatures...

            // Higher resolution tests...
            'validFor = 1000: (Hi-res) Test time equals signature time' => [$url, '1644500418.10000000', 1000, '1644500418.10000000', true],
            'validFor = 1000: (Hi-res) Test time within limits' => [$url, '1644500418.10000001', 1000, '1644500555.00000001', true],
            'validFor = 1000: (Hi-res) Test time at limit' => [$url, '1644500418.10000002', 1000, '1644501418.10000001', true],
            'validFor = 1000: (Hi-res) Test time exceeds limit' => [$url, '1644500418.10000003', 1000, '1644501418.10000004', false],
            'validFor = 1000: (Hi-res) Test time before signature time' => [$url, '1644500418.10000004', 1000, '1644500418.10000003', true],
            'validFor = 1000: (Hi-res) Replay test' => [$url, '1644500418.10000004', 1000, '1644500418.10000003', false],
        ];
    }

    protected function parseTimeString(string $time): array
    {
        return str_contains($time, '.') ?
            explode('.', $time) :
            [$time, null];
    }

    /**
     * @dataProvider dataValidFor
     */
    public function testValidFor(string $url, string $signatureTime, int $validFor, string $testTime, bool $expectedToBeValid): void
    {
        [$signatureTimeSecs, $signatureTimeNano] = $this->parseTimeString($signatureTime);
        [$testTimeSecs, $testTimeNano] = $this->parseTimeString($testTime);

        $signedUrl = $this->urlSigner->sign($url, $validFor, $signatureTimeSecs, $signatureTimeNano);
        $isValid = $this->urlSigner->isValid($signedUrl, $testTimeSecs, $testTimeNano);

        $this->assertEquals($expectedToBeValid, $isValid);
    }

    public function dataUrlModificationAttempts(): array
    {
        $url = 'https://example.com/wibble?wobble=12&wubble=toast';

        $modifyPair = fn(string $key, string $value): callable => fn(string $url) => (new Url($url))->setQueryParam($key, $value)->__toString();

        $removePair = fn(string $key): callable => fn(string $url) => (new Url($url))->removeQueryParam($key)->__toString();

        $modifyHost = fn($host): callable => fn(string $url) => (new Url($url))->setHost($host)->__toString();

        $modifyScheme = fn($protocol): callable => fn(string $url) => (new Url($url))->setScheme($protocol)->__toString();

        $modifyFragment = fn($fragment): callable => fn(string $url) => (new Url($url))->setFragment($fragment)->__toString();

        return [
            'Add key/value' => [$url, $modifyPair('wabble', 5), false],
            'Modify existing key/value' => [$url, $modifyPair('wobble', 13), false],
            'Remove key/value' => [$url, $removePair('wobble'), false],
            'Modify _until' => [$url, $modifyPair('_until', 9999999999), false],
            'Remove _until' => [$url, $removePair('_until'), false],
            'Modify _signature' => [$url, $modifyPair('_signature', '18c143d7e91a8c4a6472518c2960dc1d'), false],
            'Remove _signature' => [$url, $removePair('_signature'), false],

            // Things that are allowed to change / aren't part of the signature
            'Modify host' => [$url, $modifyHost('example.net'), true],
            'Modify protocol' => [$url, $modifyScheme('http'), true],
            'Modify fragment' => [$url, $modifyFragment('wibble=wobble=wubble=wabble'), true],
        ];
    }

    /**
     * @dataProvider dataUrlModificationAttempts
     */
    public function testUrlModificationAttempts(string $url, callable $urlModifier, bool $expectedToBeValid): void
    {
        $signedUrl = $this->urlSigner->sign($url, 10000, '1600000000');
        $modifiedUrl = $urlModifier($signedUrl);
        $isValid = $this->urlSigner->isValid($modifiedUrl, '1600001000');

        $this->assertEquals($expectedToBeValid, $isValid);
    }
}