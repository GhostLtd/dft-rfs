<?php

namespace App\Tests\Form\Type\International\InitialDetails;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\BusinessDetailsType;
use App\Tests\Form\Type\AbstractTypeTest;

class BusinessDetailsTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            [['numberOfEmployees' => 'banana'], false],

            // Missing fields
            [['businessNature' => 'banana'], false],
            [['numberOfEmployees' => '1-9'], false],

            [['numberOfEmployees' => '1-9', 'businessNature' => 'Banana'], true],
            [['numberOfEmployees' => '10-49', 'businessNature' => 'Banana'], true],
            [['numberOfEmployees' => '50-249', 'businessNature' => 'Banana'], true],
            [['numberOfEmployees' => '250-499', 'businessNature' => 'Banana'], true],
            [['numberOfEmployees' => '500-10000', 'businessNature' => 'Banana'], true],
            [['numberOfEmployees' => '10001-30000', 'businessNature' => 'Banana'], true],
            [['numberOfEmployees' => '>30000', 'businessNature' => 'Banana'], true],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $data = new SurveyResponse();

        $form = $this->factory->create(BusinessDetailsType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['numberOfEmployees'], $data->getNumberOfEmployees());
            $this->assertEquals($formData['businessNature'], $data->getBusinessNature());
        }
    }
}
