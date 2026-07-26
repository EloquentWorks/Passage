<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Definitions;

use Closure;
use InvalidArgumentException;

final class PassageDefinition
{
    private string $name;

    private int $version = 1;

    private ?string $description = null;

    private ?string $category = null;

    private bool $repeatable = false;

    private ?int $dueAfterMinutes = null;

    /** @var list<string> */
    private array $tags = [];

    /** @var array<string, mixed> */
    private array $metadata = [];

    /** @var array<string, StepDefinition> */
    private array $steps = [];

    public function __construct(private readonly string $key)
    {
        if ($key === '') {
            throw new InvalidArgumentException('A passage key cannot be empty.');
        }

        $this->name = str($key)->replace(['-', '_'], ' ')->title()->toString();
    }

    public function name(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function description(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function category(?string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function version(int $version): self
    {
        if ($version < 1) {
            throw new InvalidArgumentException('A passage version must be at least one.');
        }

        $this->version = $version;

        return $this;
    }

    public function repeatable(bool $repeatable = true): self
    {
        $this->repeatable = $repeatable;

        return $this;
    }

    public function dueAfterMinutes(?int $minutes): self
    {
        if ($minutes !== null && $minutes < 1) {
            throw new InvalidArgumentException('Passage due time must be at least one minute.');
        }

        $this->dueAfterMinutes = $minutes;

        return $this;
    }

    public function tags(string ...$tags): self
    {
        $this->tags = array_values(array_unique([...$this->tags, ...$tags]));

        return $this;
    }

    /** @param array<string, mixed> $metadata */
    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function step(string $key, ?Closure $configure = null): self
    {
        if (isset($this->steps[$key])) {
            throw new InvalidArgumentException("The [{$key}] step is already registered for [{$this->key}].");
        }

        $step = new StepDefinition($key, count($this->steps) + 1);
        if ($configure !== null) {
            $configure($step);
        }
        $this->steps[$key] = $step;

        return $this;
    }

    public function addStep(StepDefinition $step): self
    {
        if (isset($this->steps[$step->key()])) {
            throw new InvalidArgumentException("The [{$step->key()}] step is already registered for [{$this->key}].");
        }

        $this->steps[$step->key()] = $step;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->name;
    }

    public function details(): ?string
    {
        return $this->description;
    }

    public function group(): ?string
    {
        return $this->category;
    }

    public function revision(): int
    {
        return $this->version;
    }

    public function isRepeatable(): bool
    {
        return $this->repeatable;
    }

    public function dueMinutes(): ?int
    {
        return $this->dueAfterMinutes;
    }

    /** @return list<string> */
    public function tagList(): array
    {
        return $this->tags;
    }

    /** @return array<string, mixed> */
    public function meta(): array
    {
        return $this->metadata;
    }

    /** @return array<string, StepDefinition> */
    public function steps(): array
    {
        return $this->steps;
    }

    public function stepDefinition(string $key): StepDefinition
    {
        return $this->steps[$key]
            ?? throw new InvalidArgumentException("The [{$key}] step is not defined for [{$this->key}].");
    }
}
