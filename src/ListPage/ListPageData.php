<?php


namespace App\ListPage;


class ListPageData
{
    protected int $page;
    protected int $numPages;
    protected int $numRecords;
    protected array $entities;
    protected ?string $nextUrl;
    protected ?string $previousUrl;
    protected array $paginationUrls;
    protected array $parameterNames;

    public function __construct(int $page, int $numPages, int $numRecords, array $entities, ?string $nextUrl, ?string $previousUrl, array $paginationUrls, array $parameterNames)
    {
        $this->page = $page;
        $this->numPages = $numPages;
        $this->numRecords = $numRecords;
        $this->entities = $entities;
        $this->nextUrl = $nextUrl;
        $this->previousUrl = $previousUrl;
        $this->paginationUrls = $paginationUrls;
        $this->parameterNames = $parameterNames;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getNumPages(): int
    {
        return $this->numPages;
    }

    public function getNumRecords(): int
    {
        return $this->numRecords;
    }

    public function getEntities(): array
    {
        return $this->entities;
    }

    public function getNextUrl(): ?string
    {
        return $this->nextUrl;
    }

    public function getPreviousUrl(): ?string
    {
        return $this->previousUrl;
    }

    public function getPaginationUrls(): array
    {
        return $this->paginationUrls;
    }

    public function getParameterNames(): array
    {
        return $this->parameterNames;
    }
}