<?php

namespace App\Tests\NewFunctional\Wizard\Action;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * AbstractFormTestAction::applyChoiceSelections() sets radios and checkboxes on the DOM before the form is built,
 * and must keep the meaning DomCrawler's Form::setValues() gives to the same data.
 */
class ApplyChoiceSelectionsTest extends TestCase
{
    private const string HTML = <<<'HTML'
        <form method="post" action="/submit">
            <input type="checkbox" name="single" value="yes">
            <input type="checkbox" name="valueless">
            <input type="checkbox" name="emptyvalue" value="">
            <input type="checkbox" name="prechecked" value="1" checked>
            <input type="radio" name="radio" value="a">
            <input type="radio" name="radio" value="b" checked>
            <input type="checkbox" name="group[]" value="x">
            <input type="checkbox" name="group[]" value="y" checked>
            <input type="text" name="text">
            <button type="submit" id="submit">Submit</button>
        </form>
        HTML;

    /**
     * Untouched, the form submits prechecked=1, radio=b and group=[y] (and the text field, which is ignored here).
     */
    public static function selectionProvider(): array
    {
        $defaults = ['prechecked' => '1', 'radio' => 'b', 'group' => ['y']];

        return [
            'true ticks a single checkbox, whatever its value' => [['single' => true], ['single' => 'yes'] + $defaults],
            'true ticks a valueless checkbox, which submits "on"' => [['valueless' => true], ['valueless' => 'on'] + $defaults],
            'a valueless checkbox can be ticked by its implicit "on"' => [['valueless' => 'on'], ['valueless' => 'on'] + $defaults],
            'true ticks a checkbox whose value is empty, which submits ""' => [['emptyvalue' => true], ['emptyvalue' => ''] + $defaults],
            'an explicit value ticks a single checkbox' => [['single' => 'yes'], ['single' => 'yes'] + $defaults],
            'false unticks a pre-checked checkbox' => [['prechecked' => false], ['radio' => 'b', 'group' => ['y']]],
            "'' unticks a pre-checked checkbox" => [['prechecked' => ''], ['radio' => 'b', 'group' => ['y']]],
            'null unticks a pre-checked checkbox' => [['prechecked' => null], ['radio' => 'b', 'group' => ['y']]],
            'a radio takes an explicit value' => [['radio' => 'a'], ['radio' => 'a'] + $defaults],
            "'' clears a radio group" => [['radio' => ''], ['prechecked' => '1', 'group' => ['y']]],
            'a group by its name[] takes a list' => [['group[]' => ['x']], ['group' => ['x']] + $defaults],
            'a group by its bare name takes a list' => [['group' => ['x', 'y']], ['group' => ['x', 'y']] + $defaults],
            '[] clears a group' => [['group' => []], ['prechecked' => '1', 'radio' => 'b']],
        ];
    }

    #[DataProvider('selectionProvider')]
    public function testSelections(array $formData, array $expectedValues): void
    {
        $values = $this->submit($formData);
        unset($values['text']);

        $this->assertEqualsCanonicalizing($expectedValues, $values);
    }

    public static function rejectedProvider(): array
    {
        return [
            'true is ambiguous for a radio group' => [['radio' => true]],
            'true is ambiguous for a checkbox group' => [['group' => true]],
            'a value none of the choices have' => [['radio' => 'z']],
        ];
    }

    #[DataProvider('rejectedProvider')]
    public function testRejected(array $formData): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->submit($formData);
    }

    private function submit(array $formData): array
    {
        $action = new class('/submit', 'submit') extends AbstractFormTestAction {
            public function apply(Crawler $button, array $data): array
            {
                return $this->applyChoiceSelections($button, $data);
            }
        };

        $button = (new Crawler(self::HTML, 'https://example.com/'))->selectButton('submit');
        $remaining = $action->apply($button, $formData);

        return $button->form($remaining)->getPhpValues();
    }
}
