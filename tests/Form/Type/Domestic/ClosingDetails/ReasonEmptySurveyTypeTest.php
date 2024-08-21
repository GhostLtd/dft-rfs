<?php

namespace App\Tests\Form\Type\Domestic\ClosingDetails;

use App\Entity\Domestic\SurveyResponse;
use App\Form\DomesticSurvey\ClosingDetails\ReasonEmptySurveyType;
use App\Tests\Form\Type\AbstractTypeTest;

class ReasonEmptySurveyTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],
            [
                [
                    'reasonForEmptySurvey' => 'not-taxed'
                ], true
            ],
            [
                [
                    'reasonForEmptySurvey' => 'no-work'
                ], true
            ],
            [
                [
                    'reasonForEmptySurvey' => 'repair'
                ], true
            ],
            [
                [
                    'reasonForEmptySurvey' => 'site-work-only'
                ], true
            ],
            [
                [
                    'reasonForEmptySurvey' => 'holiday'
                ], true
            ],
            [
                [
                    'reasonForEmptySurvey' => 'maintenance'
                ], true
            ],
            [
                [
                    'reasonForEmptySurvey' => 'no-driver'
                ], true
            ],
            [
                [
                    'reasonForEmptySurvey' => 'other'
                ], false
            ],
            [
                [
                    'reasonForEmptySurvey' => 'other',
                    'reasonForEmptySurveyOther' => 'Banana',
                ], true
            ],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $data = new SurveyResponse();
        $form = $this->factory->create(ReasonEmptySurveyType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($data->getReasonForEmptySurvey(), $formData['reasonForEmptySurvey'] ?? null);
            $this->assertEquals($data->getReasonForEmptySurveyOther(), $formData['reasonForEmptySurveyOther'] ?? null);
        }
    }
}
