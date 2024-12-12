<?php

namespace App\Command\Dev;

use Symfony\Component\Console\Style\SymfonyStyle;

class ErrorReporter
{
    protected ?string $id;

    public function __construct(
        protected SymfonyStyle $io,
        protected string       $table,
    ) {
        $this->id = null;
    }

    public function setContext(?string $id): void
    {
        $this->id = $id;
    }

    public function getContext(): ?string
    {
        return $this->id;
    }

    public function error(string $message): void
    {
        $idPart = $this->id ? ".{$this->id}" : "";
        $this->io->error("{$this->table}{$idPart}: {$message}");
    }
}
