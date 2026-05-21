<?php

namespace App\Tests\Translation;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The quality-check "stop(s)" prefix must read naturally for a single stop and a list of stops.
 */
class QualityCheckStopPrefixTest extends KernelTestCase
{
    public function testSingularStop(): void
    {
        $this->assertSame('Stop 3', $this->stopPrefix(1, '3'));
    }

    public function testPluralStops(): void
    {
        $this->assertSame('Stops 1,2,5', $this->stopPrefix(3, '1,2,5'));
    }

    private function stopPrefix(int $count, string $numbers): string
    {
        self::bootKernel();
        /** @var TranslatorInterface $translator */
        $translator = self::getContainer()->get(TranslatorInterface::class);

        return $translator->trans(
            'admin.domestic.quality-check.stop-prefix',
            ['count' => $count, 'numbers' => $numbers],
            null,
            'en_gb',
        );
    }
}
