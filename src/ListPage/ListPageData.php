<?php


namespace App\ListPage;


use Closure;

class ListPageData
{
    public function __construct(protected int $page, protected int $numPages, protected int $numRecords, protected iterable $entities, protected ?string $nextUrl, protected ?string $previousUrl, protected array $paginationUrls, protected array $fields, protected Closure $orderUrlGenerator, protected ?string $order, protected ?string $orderDirection)
    {
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

    public function getEntities(): iterable
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

    public function getFields(): array
    {
        return $this->fields;
    }

    public function getOrderUrlGenerator(): Closure
    {
        return $this->orderUrlGenerator;
    }

    public function callOrderUrlGenerator(...$args): string
    {
        return ($this->orderUrlGenerator)(...$args);
    }

    public function getOrder(): ?string
    {
        return $this->order;
    }

    public function getOrderDirection(): ?string
    {
        return $this->orderDirection;
    }
}