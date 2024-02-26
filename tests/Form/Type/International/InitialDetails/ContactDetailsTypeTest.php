<?php

namespace App\Tests\Form\Type\International\InitialDetails;

use App\Entity\International\SurveyResponse;
use App\Form\InternationalSurvey\InitialDetails\ContactDetailsType;
use App\Tests\Form\Type\AbstractTypeTest;

class ContactDetailsTypeTest extends AbstractTypeTest
{
    protected function dataSubmit(): array
    {
        return [
            [[], false],
            [['banana' => '123'], false],

            // Missing fields
            [['contactName' => 'Mark'], false],
            [['contactEmail' => 'wibble@example.com'], false],
            [['contactTelephone' => '1234'], false],
            [['contactEmail' => 'wibble@example.com', 'contactTelephone' => '1234'], false],

            [['contactName' => 'Mark', 'contactEmail' => 'wibble@example.com'], true],
            [['contactName' => 'Mark', 'contactTelephone' => '1234'], true],
            [['contactName' => 'Mark', 'contactEmail' => 'wibble@example.com', 'contactTelephone' => '1234'], true],

            [['contactName' => 'Mark', 'contactEmail' => 'not an email', 'contactTelephone' => '1234'], false],
        ];
    }

    /**
     * @dataProvider dataSubmit
     */
    public function testSubmit(array $formData, bool $expectedValid): void
    {
        $data = new SurveyResponse();

        $form = $this->factory->create(ContactDetailsType::class, $data);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isSubmitted());

        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $this->assertEquals($formData['contactName'] ?? null, $data->getContactName());
            $this->assertEquals($formData['contactTelephone'] ?? null, $data->getContactTelephone());
            $this->assertEquals($formData['contactEmail'] ?? null, $data->getContactEmail());
        }
    }
}
