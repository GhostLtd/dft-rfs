<?php

declare(strict_types=1);

namespace App\Tests\Form\Admin\LCNI;

use App\Entity\NotificationInterceptionCompanyName;
use App\Form\Admin\LCNI\NotificationInterceptionCompanyNameDataTransformer;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NotificationInterceptionCompanyNameDataTransformerTest extends TestCase
{
    private NotificationInterceptionCompanyNameDataTransformer $transformer;

    #[\Override]
    protected function setUp(): void
    {
        $this->transformer = new NotificationInterceptionCompanyNameDataTransformer();
    }

    /**
     * @return array<string, array{0: string, 1: array<string>}>
     */
    public static function lineEndingProvider(): array
    {
        $expected = ['Acme Ltd', 'Acme Limited', 'Acme'];

        return [
            'CRLF' => ["Acme Ltd\r\nAcme Limited\r\nAcme", $expected],
            // Symfony's TextareaType normalizes submitted values to LF before
            // this transformer runs (symfony/form >= 6.4.42)
            'LF' => ["Acme Ltd\nAcme Limited\nAcme", $expected],
            'CR' => ["Acme Ltd\rAcme Limited\rAcme", $expected],
            'mixed' => ["Acme Ltd\nAcme Limited\r\nAcme", $expected],
            'blank lines and whitespace' => ["  Acme Ltd  \n\n\nAcme Limited\n   \nAcme\n", $expected],
            'single name' => ['Acme Ltd', ['Acme Ltd']],
        ];
    }

    /**
     * @param array<string> $expectedNames
     */
    #[DataProvider('lineEndingProvider')]
    public function testReverseTransformSplitsOnAnyLineEnding(string $submitted, array $expectedNames): void
    {
        $result = $this->transformer->reverseTransform($submitted);

        $this->assertSame(
            $expectedNames,
            array_map(fn(NotificationInterceptionCompanyName $n) => $n->getName(), $result)
        );
    }

    public function testReverseTransformReusesExistingEntitiesAfterTransform(): void
    {
        $existing = (new NotificationInterceptionCompanyName())->setName('Acme Ltd');
        $this->transformer->transform(new ArrayCollection([$existing]));

        $result = $this->transformer->reverseTransform("Acme Ltd\nAcme Limited");

        $this->assertCount(2, $result);
        $this->assertSame($existing, $result[0]);
        $this->assertNotSame($existing, $result[1]);
        $this->assertSame('Acme Limited', $result[1]->getName());
    }
}
