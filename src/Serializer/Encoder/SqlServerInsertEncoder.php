<?php

namespace App\Serializer\Encoder;

use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Twig\Environment;
use Twig\Extension\EscaperExtension;

class SqlServerInsertEncoder implements EncoderInterface
{
    public const FORMAT = 'sql-server-insert';
    public const TABLE_NAME_KEY = 'table-name';
    public const FORCE_STRING_FIELDS = 'force-string-fields';
    public const ROWS_PER_INSERT = 'rows-per-insert';

    private array $defaultContext = [
        self::TABLE_NAME_KEY => 'table_a',
        self::FORCE_STRING_FIELDS => [],
        self::ROWS_PER_INSERT => 1000,
    ];

    protected Environment $twig;

    public function setTwig(Environment $twig): void
    {
        $this->twig = $twig;
        $this->twig
            ->getExtension(EscaperExtension::class)
            ->setEscaper('sql', $this->sqlEscape(...));
    }

    public function __construct(array $defaultContext = [])
    {
        $this->defaultContext = array_merge($this->defaultContext, $defaultContext);
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function encode($data, $format, array $context = []): string
    {
        if (count($data) === 0) {
            return '';
        }

        return $this->twig->render('serializer/encoder/sql-server-encode.sql.twig', [
            'fields' => array_keys($data[array_key_first($data)]),
            'data' => $data,
            'table_name' => $context[self::TABLE_NAME_KEY] ?? $this->defaultContext[self::TABLE_NAME_KEY],
            'context' => array_merge($this->defaultContext, $context),
        ]) . "\n";
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function supportsEncoding($format): bool
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

        // replace characters with similar ones if we can
        setlocale(LC_CTYPE, 'en_GB.UTF-8');
        $data = iconv('UTF-8', 'ASCII//TRANSLIT', $data);

        // catch any non-ascii chars left (ignoring tab, carriage return, new line)
        $data = preg_replace('/[^\x{0020}-\x{007f}\n\r\t]/u', '?', $data);

        // escape single quote, replace carriage return/newline with sql vars (defined in template)
        $simpleReplacements = [
            "'" => "''",
            "\r" => "'+@cr+'",
            "\n" => "'+@lf+'",
        ];
        $data = str_replace(array_keys($simpleReplacements), array_values($simpleReplacements), $data);

        return "'{$data}'";
    }
}
