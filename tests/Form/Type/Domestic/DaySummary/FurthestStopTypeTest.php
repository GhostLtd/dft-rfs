<?php

namespace App\Tests\Form\Type\Domestic\DaySummary;

use App\Entity\Domestic\DaySummary;
use App\Form\DomesticSurvey\DaySummary\FurthestStopType;
use App\Form\Validator\PostcodeValidator;
use App\Tests\Form\Type\AbstractTypeTestCase;
use App\Tests\Form\Type\PostcodeChangeoverTestTrait;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\PreloadedExtension;

class FurthestStopTypeTest extends AbstractTypeTestCase
{
    use PostcodeChangeoverTestTrait;

    protected bool $featureEnabled = false;

    #[\Override]
    protected function getExtensions(): array
    {
        $changeoverHelper = $this->createChangeoverHelper($this->featureEnabled);

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension([
                    new FurthestStopType($changeoverHelper),
                ], []),
            ]
        );
    }

    #[\Override]
    protected function getValidators(): array
    {
        return array_merge(parent::getValidators(), [
            PostcodeValidator::class => new PostcodeValidator(),
        ]);
    }

    public static function dataSubmitFeatureDisabled(): array
    {
        return [
            // Valid when feature disabled - any text is accepted
            'full postcode' => [['furthestStop' => 'SO14 0YG'], true],
            'partial postcode' => [['furthestStop' => 'NN6'], true],
            'plain text' => [['furthestStop' => 'Sheffield city centre'], true],
            'number + text' => [['furthestStop' => '75 miles'], true],

            // Empty is invalid due to NotBlank constraint
            'empty' => [['furthestStop' => ''], false],
            'missing field' => [[], false],
        ];
    }

    #[DataProvider('dataSubmitFeatureDisabled')]
    public function testSubmitFeatureDisabled(array $formData, bool $expectedValid): void
    {
        $this->featureEnabled = false;

        // Re-create factory with updated feature flag
        $this->setUp();

        $data = new DaySummary();

        $form = $this->factory->create(FurthestStopType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals(
            $expectedValid,
            $form->isValid(),
            sprintf(
                "Expected form to be %s for furthestStop='%s', but got errors: %s",
                $expectedValid ? 'valid' : 'invalid',
                $formData['furthestStop'] ?? '(empty)',
                (string) $form->getErrors(true)
            )
        );

        if ($expectedValid) {
            $this->assertEquals($formData['furthestStop'] ?? null, $data->getFurthestStop());
        }
    }

    public static function dataSubmitFeatureEnabled(): array
    {
        return [
            // Valid postcodes
            'full postcode with space' => [['furthestStop' => 'SO14 0YG'], true],
            'full postcode no space' => [['furthestStop' => 'SO140YG'], true],
            'partial postcode NN6' => [['furthestStop' => 'NN6'], true],
            'partial postcode SO14' => [['furthestStop' => 'SO14'], true],
            'partial postcode lowercase' => [['furthestStop' => 'nn6'], true],

            // Invalid - plain text
            'plain text Sheffield' => [['furthestStop' => 'Sheffield city centre'], false],
            'plain text Southampton' => [['furthestStop' => 'Southampton'], false],
            'number + text' => [['furthestStop' => '75 miles'], false],

            // Empty is invalid (NotBlank constraint)
            'empty' => [['furthestStop' => ''], false],
        ];
    }

    #[DataProvider('dataSubmitFeatureEnabled')]
    public function testSubmitFeatureEnabled(array $formData, bool $expectedValid): void
    {
        $this->featureEnabled = true;

        // Re-create factory with updated feature flag
        $this->setUp();

        // Create entity with mocked chain to trigger postcode-only mode
        $data = $this->createDaySummaryWithMockedChain(true);

        $form = $this->factory->create(FurthestStopType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals(
            $expectedValid,
            $form->isValid(),
            sprintf(
                "Expected form to be %s for furthestStop='%s', but got errors: %s",
                $expectedValid ? 'valid' : 'invalid',
                $formData['furthestStop'] ?? '(empty)',
                (string) $form->getErrors(true)
            )
        );
    }
}
