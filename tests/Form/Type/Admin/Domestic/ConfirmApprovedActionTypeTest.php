<?php

namespace App\Tests\Form\Type\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Form\Admin\DomesticSurvey\ConfirmApprovedActionType;
use App\Tests\Form\Type\Admin\AbstractSurveyTypeTest;

class ConfirmApprovedActionTypeTest extends AbstractSurveyTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['reasonForUnfilledSurvey' => ''], false],
            [['reasonForUnfilledSurvey' => 'Computer caught fire'], false],

            [['reasonForUnfilledSurvey' => 'not-delivered'], true],
            [['reasonForUnfilledSurvey' => 'respondent-refused'], true],
            [['reasonForUnfilledSurvey' => 'excused-personal'], true],
            [['reasonForUnfilledSurvey' => 'excused-other'], true],
            [['reasonForUnfilledSurvey' => 'other'], true],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $form = $this->factory->create(ConfirmApprovedActionType::class, null, [
            'translation_key_prefix' => 'test_',
        ]);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $data = $form->getData();

            $this->assertInstanceOf(Survey::class, $data);
            $this->assertEquals($formData['reasonForUnfilledSurvey'], $data->getReasonForUnfilledSurvey());
        }
    }
}
