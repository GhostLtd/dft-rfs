<?php

namespace App\Tests\NewFunctional\Wizard\Action;

class PathTestAction extends AbstractAction
{
    public const OPTION_EXPECTED_PATH_REGEX = 'path-is-regex';

    public function __construct(protected string $expectedPath, protected array $options = [])
    {
    }

    public function getExpectedPath(): string
    {
        return $this->expectedPath;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    #[\Override]
    public function perform(Context $context): void
    {
        $this->outputHeader($context);

        $expectedPath = $this->getResolvedExpectedPath($context);

        $isExpectedPathRegex = $this->isExpectedPathRegex();
        $this->outputPathDebug($context, $expectedPath, $isExpectedPathRegex);

        $context->getTestCase()->assertPathMatches(
            $expectedPath,
            $isExpectedPathRegex
        );
    }

    protected function isExpectedPathRegex(): bool
    {
        return boolval($this->getOptions()[PathTestAction::OPTION_EXPECTED_PATH_REGEX] ?? false);
    }

    protected function getResolvedExpectedPath(Context $context): string
    {
        return $context->getConfig('basePath') . $this->getExpectedPath();
    }

    protected function outputPathDebug(Context $context, string $expectedPath, bool $isExpectedPathRegex, string $prefix='  '): void
    {
        $regexFlag = $isExpectedPathRegex ? ' <info>(REGEX)</info>' : '';
        $context->outputWithPrefix("<comment>Check path matches:</comment> {$expectedPath}{$regexFlag}", $prefix, 2);
    }
}