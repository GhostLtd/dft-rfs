<?php

namespace App\Tests\Validator;

use App\Entity\Domestic\Survey;
use App\Form\Validator\CommaSeparatedEmails;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Validator\Validation;

class CommaSeparatedEmailsValidatorTest extends WebTestCase
{
    #[\Override]
    public function setUp(): void
    {
        static::bootKernel();
    }

    public function dataCommaSeparatedEmails(): array
    {
        // The validator checks that all data contained within are valid emails
        // It doesn't check for empty values like ,,,,, filtering those out will be done elsewhere (see tests below)
        return [
            ['', true],
            ['test@example.com', true],
            ['test@example.com,toast@example.com', true],
            ['test@example.com,', true],
            ['test@example.com,,,,,', true],
            ['toast@example,toast@example.com', false],
            ['test@example.com,toast@com', false],
            ['test@example.com,,,,,,toast@com', false],
            ['2', false],
        ];
    }

    /**
     * @dataProvider dataCommaSeparatedEmails
     */
    public function testCommaSeparatedEmails(string $input, bool $expectedValid): void
    {
        $validator = Validation::createValidator();
        $violations = $validator->validate($input, [
            new CommaSeparatedEmails(),
        ]);

        $this->assertEquals($expectedValid, $violations->count() === 0);
    }

    public function dataGetArrayOfInvitationEmails(): array
    {
        return [
            [null, 0],
            ['', 0],
            ['one@example.com,two@example.com', 2],
            ['test@example.com,,,,,toast@example.com', 2],
            ['one,two,three', 3],
            [',,one,,two,,three,,', 3],
            [',   ,   one   ,   ,   two   ,,three,,', 3],
            ['one@example.com,one@example.com', 1],
            ['one@example.com,two@example.com,one@example.com', 2],
        ];
    }

    /**
     * @dataProvider dataGetArrayOfInvitationEmails
     */
    public function testGetArrayOfInvitationEmails(?string $input, int $expectedEmailCount): void
    {
        $survey = new Survey();
        $survey->setInvitationEmails($input);

        $emails = $survey->getArrayOfInvitationEmails();

        $this->assertCount($expectedEmailCount, $emails);
    }

    /**
     * @dataProvider dataGetArrayOfInvitationEmails
     */
    public function testHasValidInvitationEmails(?string $input, int $expectedEmailCount): void
    {
        $survey = new Survey();
        $survey->setInvitationEmails($input);

        $hasValidEmails = $survey->hasValidInvitationEmails();

        $this->assertEquals($expectedEmailCount > 0, $hasValidEmails);
    }
}