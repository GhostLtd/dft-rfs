<?php

namespace App\ListPage;

class Field
{
    const TYPE_TEXT = 'text';
    const TYPE_SELECT = 'select';

    protected string $id;
    protected ?string $type;
    protected ?array $choices;
    protected string $label;
    protected ?string $propertyPath;
    protected array $cellOptions;
    protected bool $sortable;

    public function __construct(string $label, ?string $propertyPath, array $cellOptions = [])
    {
        $this->id = self::generateId($label);

        $this->label = $label;
        $this->propertyPath = $propertyPath;
        $this->cellOptions = $cellOptions;

        $this->type = null;
        $this->choices = null;

        $this->sortable = false;
    }

    public function sortable(): self {
        $this->sortable = true;
        return $this;
    }

    public function textFilterable(): self {
        $this->type = self::TYPE_TEXT;
        return $this;
    }

    public function selectFilterable(array $choices): self
    {
        $this->choices = $choices;
        $this->type = self::TYPE_SELECT;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getChoices(): ?array
    {
        return $this->choices;
    }

    public static function generateId(string $label): string
    {
        $tmp = preg_replace('/[^a-zA-Z0-9\s]/', '', $label);
        $tmp = preg_replace('/\s+/', '_', $tmp);
        return strtolower($tmp);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getPropertyPath(): ?string
    {
        return $this->propertyPath;
    }

    public function getSortable(): bool
    {
        return $this->sortable;
    }

    public function getCellOptions(): array
    {
        return $this->cellOptions;
    }

    // -----

    public function getParameterName(): string
    {
        $propertyPathParts = explode('.', $this->propertyPath);
        return end($propertyPathParts);
    }
}