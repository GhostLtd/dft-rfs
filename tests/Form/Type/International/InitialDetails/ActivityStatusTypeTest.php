<?php

namespace App\Tests\Form\Type\International\InitialDetails;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\ActivityStatusType;
use App\Tests\Form\Type\AbstractTypeTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class ActivityStatusTypeTest extends AbstractTypeTestCase
{
    public static function dataSubmit(): array
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

    #[DataProvider('dataSubmit')]
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
