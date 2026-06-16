<?php

namespace App\Tests\Form\Type;

use App\Entity\Domestic\Day;
use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Entity\Domestic\Survey;
use App\Entity\Domestic\SurveyResponse;
use App\Features;
use App\Form\Validator\PostcodeValidator;
use App\Utility\PostcodeChangeoverHelper;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Form\PreloadedExtension;

abstract class AbstractDestinationTypeTestCase extends AbstractTypeTestCase
{
    use PostcodeChangeoverTestTrait;

    abstract protected function getDataClass(): string;
    abstract protected function getFormClass(): string;

    protected bool $featureEnabled = false;

    #[\Override]
    protected function getExtensions(): array
    {
        $changeoverHelper = $this->createChangeoverHelper($this->featureEnabled);

        $formClass = $this->getFormClass();

        return array_merge(
            parent::getExtensions(),
            [
                new PreloadedExtension([
                    new $formClass($changeoverHelper),
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
            [[], false],
            [['banana' => '123'], false],

            [['destinationLocation' => 'SO14 0YG', 'goodsUnloaded' => '0'], true],
            [['destinationLocation' => 'SO14 0YG', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '0'], true],
            [['destinationLocation' => 'SO14 0YG', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '1'], true],
            [['destinationLocation' => 'SO14 0YG', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '2'], true],
            [['destinationLocation' => 'SO14 0YG', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '4'], true],

            [['destinationLocation' => 'SO14 0YG', 'goodsUnloaded' => '2', 'goodsTransferredTo' => '4'], false],
            [['destinationLocation' => 'SO14 0YG', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '3'], false],
            [['destinationLocation' => '', 'goodsUnloaded' => '1', 'goodsTransferredTo' => '4'], false],

            // These would be invalid if feature enabled, but are valid when disabled
            [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '0'], true],
            [['destinationLocation' => '75 miles', 'goodsUnloaded' => '0'], true],
        ];
    }

    #[DataProvider('dataSubmitFeatureDisabled')]
    public function testSubmitFeatureDisabled(array $formData, bool $expectedValid): void
    {
        $this->featureEnabled = false;

        // Re-create factory with updated feature flag
        $this->setUp();

        $dataClass = $this->getDataClass();
        $data = new $dataClass();

        $form = $this->factory->create($this->getFormClass(), $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['destinationLocation'] ?? null, $data->getDestinationLocation());
            $this->assertEquals($formData['goodsUnloaded'] ?? null, $data->getGoodsUnloaded());
            $this->assertEquals($formData['goodsTransferredTo'] ?? null, $data->getGoodsTransferredTo());
        }
    }

    public static function dataSubmitFeatureEnabled(): array
    {
        return [
            // Valid postcodes
            'full postcode with space' => [['destinationLocation' => 'SO14 0YG', 'goodsUnloaded' => '0'], true],
            'full postcode no space' => [['destinationLocation' => 'SO140YG', 'goodsUnloaded' => '0'], true],
            'partial postcode NN6' => [['destinationLocation' => 'NN6', 'goodsUnloaded' => '0'], true],
            'partial postcode SO14' => [['destinationLocation' => 'SO14', 'goodsUnloaded' => '0'], true],
            'partial postcode lowercase' => [['destinationLocation' => 'nn6', 'goodsUnloaded' => '0'], true],

            // Invalid - plain text
            'plain text Southampton' => [['destinationLocation' => 'Southampton', 'goodsUnloaded' => '0'], false],
            'plain text Sheffield' => [['destinationLocation' => 'Sheffield city centre', 'goodsUnloaded' => '0'], false],
            'number + text' => [['destinationLocation' => '75 miles', 'goodsUnloaded' => '0'], false],

            // Empty is invalid (NotBlank constraint)
            'empty' => [['destinationLocation' => '', 'goodsUnloaded' => '0'], false],
        ];
    }

    #[DataProvider('dataSubmitFeatureEnabled')]
    public function testSubmitFeatureEnabled(array $formData, bool $expectedValid): void
    {
        $this->featureEnabled = true;

        // Re-create factory with updated feature flag
        $this->setUp();

        // Create entity with mocked chain to trigger postcode-only mode
        $dataClass = $this->getDataClass();
        if ($dataClass === DayStop::class) {
            $data = $this->createDayStopWithMockedChain(true);
        } else {
            $data = $this->createDaySummaryWithMockedChain(true);
        }

        $form = $this->factory->create($this->getFormClass(), $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals(
            $expectedValid,
            $form->isValid(),
            sprintf(
                "Expected form to be %s for destinationLocation='%s', but got errors: %s",
                $expectedValid ? 'valid' : 'invalid',
                $formData['destinationLocation'] ?? '(empty)',
                (string) $form->getErrors(true)
            )
        );
    }
}
