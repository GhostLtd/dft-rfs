<?php

declare(strict_types=1);

namespace App\Command;

final readonly class SqlObject
{
    /** @param list<string> $depends */
    public function __construct(
        public string $name,
        public SqlObjectType $type,
        public string $body,
        public array $depends,
        public string $path,
    ) {
    }

    public static function fromFile(string $path, SqlObjectType $type): self
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException(sprintf('Could not read %s', $path));
        }
        return new self(
            name: basename($path, '.sql'),
            type: $type,
            body: $contents,
            depends: self::parseDepends($contents),
            path: $path,
        );
    }

    /** @return list<string> */
    private static function parseDepends(string $sql): array
    {
        $deps = [];
        foreach (explode("\n", $sql) as $line) {
            $trimmed = ltrim($line);
            if ($trimmed === '') {
                continue;
            }
            if (!str_starts_with($trimmed, '--')) {
                break;
            }
            if (preg_match('/^--\s*@depends\s+(\S+)/', $trimmed, $m)) {
                $deps[] = $m[1];
            }
        }
        return $deps;
    }
}
