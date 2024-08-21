<?php

namespace App\Tests\Form\Type\International\InitialDetails;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\ActivityStatusType;
use App\Tests\Form\Type\AbstractTypeTest;

class ActivityStatusTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['activityStatus' => 'banana'], false],

            [['activityStatus' => 'ceased-trading'], true],
            [['activityStatus' => 'only-domestic-work'], true],
            [['activityStatus' => 'still-active'], true],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $data = new SurveyResponse();

        $form = $this->factory->create(ActivityStatusType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['activityStatus'], $data->getActivityStatus());
        }
    }
}
