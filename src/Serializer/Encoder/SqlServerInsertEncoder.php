<?php

namespace App\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Twig\Environment;
use Twig\Extension\EscaperExtension;

class SqlServerInsertEncoder implements EncoderInterface
{
    const FORMAT = 'sql-server-insert';
    const TABLE_NAME_KEY = 'table-name';

    private $defaultContext = [
        self::TABLE_NAME_KEY => 'table_a',
    ];

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @param Environment $twig
     */
    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
        $this->twig
            ->getExtension(EscaperExtension::class)
            ->setEscaper('sql', [$this, 'sqlEscape']);
    }

    /**
     * @param array $defaultContext
     */
    public function __construct($defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    public function encode($data, $format, array $context = [])
    {
        if (count($data) === 0) {
            return '';
        }

        return $this->twig->render('serializer/encoder/sql-server-encode.sql.twig', [
            'fields' => array_keys($data[0]),
            'data' => $data,
            'table_name' => $context[self::TABLE_NAME_KEY] ?? $this->defaultContext[self::TABLE_NAME_KEY],
        ]) . "\n";
    }

    /**
     * {@inheritdoc}
     */
    public function supportsEncoding($format)
    {
        return self::FORMAT === $format;
    }

    public function sqlEscape(Environment $twig, $data, $charset)
    {
        if (is_null($data)) {
            return 'NULL';
        }
        if (is_numeric($data)) {
            return $data;
        }
        if (empty($data)) {
            return "''";
        }

        $simpleReplacements = [
            "\"" => '\\"',
            "\\" => '\\\\',
            "/" => '\\/',
            chr(8) => "\\b",
            "\f" => "\\f",
            "\n" => '\\n',
            "\r" => '\\r',
            "\t" => '\\t',
            "'" => "''",
        ];
        $data = str_replace(array_keys($simpleReplacements), array_values($simpleReplacements), $data);

        $data = preg_replace_callback('/[\x{0000}-\x{001f}]/', function ($match) use($data) {
            return sprintf("\\u%'04d", mb_ord($match[0]));
        }, $data);

        return "'{$data}'";
    }
}
