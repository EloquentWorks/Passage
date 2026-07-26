<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Definitions;

use Closure;
use EloquentWorks\Passage\Contracts\StepCondition;
use InvalidArgumentException;

final class StepDefinition
{
    private string $name;

    private bool $required = true;

    /** @var list<string> */
    private array $prerequisites = [];

    private ?string $route = null;

    /** @var array<string, scalar|null> */
    private array $routeParameters = [];

    private ?string $url = null;

    private ?string $description = null;

    private ?int $dueAfterMinutes = null;

    private bool $allowRetry = true;

    private ?int $maximumAttempts = null;

    /** @var Closure|class-string<StepCondition>|null */
    private Closure|string|null $completionCondition = null;

    /** @var Closure|class-string<StepCondition>|null */
    private Closure|string|null $visibilityCondition = null;

    /** @var array<string, mixed> */
    private array $metadata = [];

    public function __construct(
        private readonly string $key,
        private readonly int $position,
    ) {
        if ($key === '') {
            throw new InvalidArgumentException('A passage step key cannot be empty.');
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

    public function required(bool $required = true): self
    {
        $this->required = $required;

        return $this;
    }

    public function optional(): self
    {
        return $this->required(false);
    }

    public function dependsOn(string ...$stepKeys): self
    {
        $this->prerequisites = array_values(array_unique([
            ...$this->prerequisites,
            ...$stepKeys,
        ]));

        return $this;
    }

    /** @param array<string, scalar|null> $parameters */
    public function route(string $route, array $parameters = []): self
    {
        $this->route = $route;
        $this->routeParameters = $parameters;
        $this->url = null;

        return $this;
    }

    public function url(string $url): self
    {
        $this->url = $url;
        $this->route = null;
        $this->routeParameters = [];

        return $this;
    }

    public function dueAfterMinutes(?int $minutes): self
    {
        if ($minutes !== null && $minutes < 1) {
            throw new InvalidArgumentException('Step due time must be at least one minute.');
        }

        $this->dueAfterMinutes = $minutes;

        return $this;
    }

    public function retryable(bool $allowRetry = true, ?int $maximumAttempts = null): self
    {
        if ($maximumAttempts !== null && $maximumAttempts < 1) {
            throw new InvalidArgumentException('Maximum attempts must be at least one.');
        }

        $this->allowRetry = $allowRetry;
        $this->maximumAttempts = $maximumAttempts;

        return $this;
    }

    /** @param Closure|class-string<StepCondition> $condition */
    public function completeWhen(Closure|string $condition): self
    {
        $this->completionCondition = $condition;

        return $this;
    }

    /** @param Closure|class-string<StepCondition> $condition */
    public function visibleWhen(Closure|string $condition): self
    {
        $this->visibilityCondition = $condition;

        return $this;
    }

    /** @param array<string, mixed> $metadata */
    public function metadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function position(): int
    {
        return $this->position;
    }

    public function label(): string
    {
        return $this->name;
    }

    public function details(): ?string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }

    /** @return list<string> */
    public function prerequisites(): array
    {
        return $this->prerequisites;
    }

    public function routeName(): ?string
    {
        return $this->route;
    }

    /** @return array<string, scalar|null> */
    public function routeParameters(): array
    {
        return $this->routeParameters;
    }

    public function directUrl(): ?string
    {
        return $this->url;
    }

    public function dueMinutes(): ?int
    {
        return $this->dueAfterMinutes;
    }

    public function allowsRetry(): bool
    {
        return $this->allowRetry;
    }

    public function maxAttempts(): ?int
    {
        return $this->maximumAttempts;
    }

    /** @return Closure|class-string<StepCondition>|null */
    public function completionCondition(): Closure|string|null
    {
        return $this->completionCondition;
    }

    /** @return Closure|class-string<StepCondition>|null */
    public function visibilityCondition(): Closure|string|null
    {
        return $this->visibilityCondition;
    }

    /** @return array<string, mixed> */
    public function meta(): array
    {
        return $this->metadata;
    }
}
