<?php

namespace App\Tests\Form\Type;

use App\Entity\Domestic\DayStop;
use App\Entity\Domestic\DaySummary;
use App\Form\Validator\PostcodeValidator;
use App\Utility\PostcodeChangeoverHelper;
use Symfony\Component\Form\PreloadedExtension;

abstract class AbstractOriginTypeTest extends AbstractTypeTest
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

    protected function dataSubmitFeatureDisabled(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['originLocation' => 'SO14 0YG', 'goodsLoaded' => '0'], true],
            [['originLocation' => 'SO14 0YG', 'goodsLoaded' => '1', 'goodsTransferredFrom' => '0'], true],
            [['originLocation' => 'SO14 0YG', 'goodsLoaded' => '1', 'goodsTransferredFrom' => '1'], true],
            [['originLocation' => 'SO14 0YG', 'goodsLoaded' => '1', 'goodsTransferredFrom' => '2'], true],
            [['originLocation' => 'SO14 0YG', 'goodsLoaded' => '1', 'goodsTransferredFrom' => '4'], true],

            [['originLocation' => 'SO14 0YG', 'goodsLoaded' => '2', 'goodsTransferredFrom' => '4'], false],
            [['originLocation' => 'SO14 0YG', 'goodsLoaded' => '1', 'goodsTransferredFrom' => '3'], false],
            [['originLocation' => '', 'goodsLoaded' => '1', 'goodsTransferredFrom' => '4'], false],

            // These would be invalid if feature enabled, but are valid when disabled
            [['originLocation' => 'Southampton', 'goodsLoaded' => '0'], true],
            [['originLocation' => '75 miles', 'goodsLoaded' => '0'], true],
        ];
    }

    /**
     * @dataProvider dataSubmitFeatureDisabled
     */
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
            $this->assertEquals($formData['originLocation'] ?? null, $data->getOriginLocation());
            $this->assertEquals($formData['goodsLoaded'] ?? null, $data->getGoodsLoaded());
            $this->assertEquals($formData['goodsTransferredFrom'] ?? null, $data->getGoodsTransferredFrom());
        }
    }

    protected function dataSubmitFeatureEnabled(): array
    {
        return [
            // Valid postcodes
            'full postcode with space' => [['originLocation' => 'SO14 0YG', 'goodsLoaded' => '0'], true],
            'full postcode no space' => [['originLocation' => 'SO140YG', 'goodsLoaded' => '0'], true],
            'partial postcode NN6' => [['originLocation' => 'NN6', 'goodsLoaded' => '0'], true],
            'partial postcode SO14' => [['originLocation' => 'SO14', 'goodsLoaded' => '0'], true],
            'partial postcode lowercase' => [['originLocation' => 'nn6', 'goodsLoaded' => '0'], true],

            // Invalid - plain text
            'plain text Southampton' => [['originLocation' => 'Southampton', 'goodsLoaded' => '0'], false],
            'plain text Sheffield' => [['originLocation' => 'Sheffield city centre', 'goodsLoaded' => '0'], false],
            'number + text' => [['originLocation' => '75 miles', 'goodsLoaded' => '0'], false],

            // Empty is invalid (NotBlank constraint)
            'empty' => [['originLocation' => '', 'goodsLoaded' => '0'], false],
        ];
    }

    /**
     * @dataProvider dataSubmitFeatureEnabled
     */
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
                "Expected form to be %s for originLocation='%s', but got errors: %s",
                $expectedValid ? 'valid' : 'invalid',
                $formData['originLocation'] ?? '(empty)',
                (string) $form->getErrors(true)
            )
        );
    }
}
