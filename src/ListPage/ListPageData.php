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

    public function getPaginationData(): array
    {
        $page = $this->getPage();

        $items = array_map(
            function(array $entry) use ($page) {
                [$number, $href] = $entry;

                return match($number) {
                    '...' => ['ellipsis' => true],
                    default => $page === $number ?
                        ['number' => $number, 'href' => $href, 'current' => true] :
                        ['number' => $number, 'href' => $href]
                };
            }, $this->getPaginationUrls()
        );

        return [
            'previous' => ['href' => $this->getPreviousUrl()],
            'next' => ['href' => $this->getNextUrl()],
            'items' => $items,
        ];
    }
}