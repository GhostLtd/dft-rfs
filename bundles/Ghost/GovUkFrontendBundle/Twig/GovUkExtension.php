<?php

namespace Ghost\GovUkFrontendBundle\Twig;

use Ghost\GovUkFrontendBundle\Twig\TokenParser\StrictTokenParser;
use ReflectionClass;
use ReflectionException;
use Twig\Error\RuntimeError;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;
use function twig_trim_filter;

class GovUkExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('njIndent', [$this, 'indent']),
            new TwigFilter('njTrim', [$this, 'trim']),
        ];
    }

    public function getTokenParsers()
    {
        return [
            new StrictTokenParser(),
        ];
    }

    /**
     * Implementation of Nunjucks' indent filter
     * http://mozilla.github.io/nunjucks/templating.html#indent.
     *
     * @param $string
     *
     * @return string
     *
     * @throws ReflectionException
     */
    public function indent($string, int $width = 4, bool $indentFirst = false)
    {
        $lines = explode("\n", $string);
        $prefix = str_repeat(' ', $width);

        $replacement = join('', array_map(function ($line, $i) use ($prefix, $indentFirst) {
            return (0 === $i && !$indentFirst) ?
                "{$line}\n" :
                "{$prefix}{$line}\n";
        }, $lines, array_keys($lines)));

        return $this->applyFilterRetainingSafeStatus($string, $replacement);
    }

    /**
     * Update trim so that it doesn't mark already-safe content as not safe.
     *
     * @param $string
     * @param null   $characterMask
     * @param string $side
     *
     * @return string
     *
     * @throws RuntimeError
     * @throws ReflectionException
     */
    public function trim($string, $characterMask = null, $side = 'both')
    {
        $replacement = twig_trim_filter($string, $characterMask, $side);

        return $this->applyFilterRetainingSafeStatus($string, $replacement);
    }

    /**
     * @param $node
     *
     * @return string|Markup
     *
     * @throws ReflectionException
     */
    protected function applyFilterRetainingSafeStatus($node, string $replacement)
    {
        if ($node instanceof Markup) {
            $reflClass = new ReflectionClass($node);
            $prop = $reflClass->getProperty('content');
            $prop->setAccessible(true);
            $prop->setValue($node, $replacement);

            return $node;
        }

        return $replacement;
    }
}
