<?php

namespace App\Tests\Form\Type\Admin\Domestic;

use App\Entity\Domestic\Survey;
use App\Entity\LongAddress;
use App\Form\Admin\DomesticSurvey\AddSurveyType;
use App\Tests\Form\Type\Admin\AbstractSurveyTypeTest;

class AddSurveyTypeTest extends AbstractSurveyTypeTest
{
    protected function dataNeitherResendNorReissue(): array
    {
        $now = new \DateTime();

        $validDate = [
            'day' => $now->format('d'),
            'month' => $now->format('m'),
            'year' => $now->format('Y'),
        ];

        $validData = [
            'registrationMark' => 'AB01 ABC',
            'isNorthernIreland' => '1',
            'surveyPeriodStart' => $validDate
        ];

        $override = fn(array $data): array => array_merge($validData, $data);
        $overrideDate = fn(array $data): array => array_merge($validData, ['validDate' => array_merge($validDate, $data)]);
        $overrideAddress = fn(array $data): array => array_merge($validData, ['invitationAddress' => ['lines' => $data]]);

        return [
            [[], false],
            [['banana' => '123'], false],

            [$validData, true],
            [$override(['isNorthernIreland' => '0']), true],
            [$override(['invitationEmails' => 'apple@example.com']), true],
            [$override(['invitationEmails' => 'apple@example.com,banana@example.com']), true],
            [$override(['invitationEmails' => '     apple@example.com,     banana@example.com     ']), true],
            [$overrideAddress(['line1' => 'Somewhere', 'line2' => 'Whatever', 'postcode' => 'PO20 2AA']), true],
            [$overrideAddress(['line1' => 'Somewhere', 'line5' => 'Whatever', 'postcode' => 'PO20 2AA']), true],

            // Leaving mandatory fields blank
            [$override(['registrationMark' => '',]), false],
            [$override(['isNorthernIreland' => '',]), false],
            [$overrideDate(['day' => '',]), false],
            [$overrideDate(['month' => '',]), false],
            [$overrideDate(['year' => '',]), false],

            // Invalid values
            [$override(['registrationMark' => 'AB1 ABC']), false],
            [$override(['isNortherIreland' => '2']), false],
            [$override(['invitationEmails' => 'apple@example.com,banana']), false],
            [$override(['invitationEmails' => '     ap   ple@example.com,     banana@exam   ple.com     ']), false],
            [$overrideAddress(['line1' => 'Somewhere', 'postcode' => 'PO20 2AA']), false],
            [$overrideAddress(['line2' => 'Somewhere', 'line3' => 'Whatever', 'postcode' => 'PO20 2AA']), false],
            [$overrideAddress(['line1' => 'Somewhere', 'line2' => 'Whatever', 'postcode' => 'ZZ20 22A']), false],
        ];
    }

    /**
     * @dataProvider dataNeitherResendNorReissue
     */
    public function testNeitherResendNorReissue(array $formData, bool $expectedValid): void
    {
        $this->performTest($formData, $expectedValid);
    }

    protected function dataResend(): array
    {
        $validData = [];

        $override = fn(array $data): array => array_merge($validData, $data);
        $overrideAddress = fn(array $data): array => array_merge($validData, ['invitationAddress' => ['lines' => $data]]);

        return [
            [['banana' => '123'], false],

            // When is_resend:
            // - registrationMark field is disabled
            // - isNorthernIreland field is disabled
            // - surveyPeriodStart field is disabled

            [$validData, true],

            [$override(['invitationEmails' => 'apple@example.com']), true],
            [$override(['invitationEmails' => 'apple@example.com,banana@example.com']), true],
            [$override(['invitationEmails' => '     apple@example.com,     banana@example.com     ']), true],
            [$overrideAddress(['line1' => 'Somewhere', 'line2' => 'Whatever', 'postcode' => 'PO20 2AA']), true],
            [$overrideAddress(['line1' => 'Somewhere', 'line5' => 'Whatever', 'postcode' => 'PO20 2AA']), true],

            // Invalid values
            [$override(['invitationEmails' => 'apple@example.com,banana']), false],
            [$override(['invitationEmails' => '     ap   ple@example.com,     banana@exam   ple.com     ']), false],
            [$overrideAddress(['line1' => 'Somewhere', 'postcode' => 'PO20 2AA']), false],
            [$overrideAddress(['line2' => 'Somewhere', 'line3' => 'Whatever', 'postcode' => 'PO20 2AA']), false],
            [$overrideAddress(['line1' => 'Somewhere', 'line2' => 'Whatever', 'postcode' => 'ZZ20 22A']), false],
        ];
    }

    /**
     * @dataProvider dataResend
     */
    public function testResend(array $formData, bool $expectedValid): void
    {
        [$survey, $data] = $this->getExistingSurveyAndData();
        $this->performTest($formData, $expectedValid, ['is_resend' => true], $survey, $data);
    }


    protected function dataReissue(): array
    {
        $now = new \DateTime();

        $validDate = [
            'day' => $now->format('d'),
            'month' => $now->format('m'),
            'year' => $now->format('Y'),
        ];

        $validData = [
            'surveyPeriodStart' => $validDate
        ];

        $override = fn(array $data): array => array_merge($validData, $data);
        $overrideDate = fn(array $data): array => array_merge($validData, ['validDate' => array_merge($validDate, $data)]);
        $overrideAddress = fn(array $data): array => array_merge($validData, ['invitationAddress' => ['lines' => $data]]);

        return [
            [[], false],
            [['banana' => '123'], false],

            // When is_reissue:
            // - registrationMark field is disabled
            // - isNorthernIreland field is disabled

            // (i.e. slightly different from is_resend)

            [$validData, true],

            [$override(['invitationEmails' => 'apple@example.com']), true],
            [$override(['invitationEmails' => 'apple@example.com,banana@example.com']), true],
            [$override(['invitationEmails' => '     apple@example.com,     banana@example.com     ']), true],
            [$overrideAddress(['line1' => 'Somewhere', 'line2' => 'Whatever', 'postcode' => 'PO20 2AA']), true],
            [$overrideAddress(['line1' => 'Somewhere', 'line5' => 'Whatever', 'postcode' => 'PO20 2AA']), true],

            // Leaving mandatory fields blank
            [$overrideDate(['day' => '',]), false],
            [$overrideDate(['month' => '',]), false],
            [$overrideDate(['year' => '',]), false],

            // Invalid values
            [$override(['invitationEmails' => 'apple@example.com,banana']), false],
            [$override(['invitationEmails' => '     ap   ple@example.com,     banana@exam   ple.com     ']), false],
            [$overrideAddress(['line1' => 'Somewhere', 'postcode' => 'PO20 2AA']), false],
            [$overrideAddress(['line2' => 'Somewhere', 'line3' => 'Whatever', 'postcode' => 'PO20 2AA']), false],
            [$overrideAddress(['line1' => 'Somewhere', 'line2' => 'Whatever', 'postcode' => 'ZZ20 22A']), false],
        ];
    }

    /**
     * @dataProvider dataReissue
     */
    public function testReissue(array $formData, bool $expectedValid): void
    {
        [$survey, $data] = $this->getExistingSurveyAndData();
        $this->performTest($formData, $expectedValid, ['is_reissue' => true], $survey, $data);
    }

    /**
     * @return array{0: Survey, 1: array<string, string>}
     */
    protected function getExistingSurveyAndData(): array
    {
        $now = new \DateTime();
        $survey = (new Survey())
            ->setSurveyPeriodStart($now)
            ->setSurveyPeriodEnd($now->add(new \DateInterval('P7D')))
            ->setRegistrationMark('BC01 BCD')
            ->setIsNorthernIreland(true);

        $data = [
            'isNorthernIreland' => '1',
            'registrationMark' => 'BC01BCD',
        ];

        return [$survey, $data];
    }

    protected function performTest(array $formData, bool $expectedValid, array $formOptions=[], Survey $survey=null, array $existingData=[]): void
    {
        $form = $this->factory->create(AddSurveyType::class, $survey, $formOptions);

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($expectedValid, $form->isValid());

        if ($expectedValid) {
            $data = $form->getData();
            $formData = array_merge($existingData, $formData);

            $expectedRegistrationMark = $formData['registrationMark'] ?? null;
            if ($expectedRegistrationMark) {
                $expectedRegistrationMark = str_replace(' ', '', $expectedRegistrationMark);
            }

            $expectedEmails = $formData['invitationEmails'] ?? null;
            if ($expectedEmails) {
                $expectedEmails = trim($expectedEmails);
            }

            $formAddress = $formData['invitationAddress']['lines'] ?? null;
            if ($formAddress) {
                $expectedAddress = (new LongAddress())
                    ->setLine1($formAddress['line1'] ?? null)
                    ->setLine2($formAddress['line2'] ?? null)
                    ->setLine3($formAddress['line3'] ?? null)
                    ->setLine4($formAddress['line4'] ?? null)
                    ->setLine5($formAddress['line5'] ?? null)
                    ->setLine6($formAddress['line6'] ?? null)
                    ->setPostcode($formAddress['postcode'] ?? null);
            } else {
                $expectedAddress = new LongAddress();
            }

            $this->assertInstanceOf(Survey::class, $data);
            $this->assertEquals($expectedRegistrationMark, $data->getRegistrationMark());
            $this->assertEquals($formData['isNorthernIreland'] ?? null, $data->getIsNorthernIreland());
            $this->assertEquals($expectedEmails, $data->getInvitationEmails());
            $this->assertEquals($expectedAddress, $data->getInvitationAddress());
        }
    }
}
