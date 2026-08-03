<?php

namespace EloquentWorks\Passage\Definitions;

use Closure;
use EloquentWorks\Passage\Contracts\StepCondition;
use InvalidArgumentException;

final class StepDefinition
{
    /**
     * The name of the step.
     *
     * @var string
     */
    private string $name;

    /**
     * Whether the step is required or optional.
     *
     * @var bool
     */
    private bool $required = true;

    /**
     * The prerequisites for the step.
     *
     * @var list<string>
     */
    private array $prerequisites = [];

    /**
     * The route name for the step.
     *
     * @var string|null
     */
    private ?string $route = null;

    /**
     * The route parameters for the step.
     *
     * @var array<string, scalar|null>
     */
    private array $routeParameters = [];

    /**
     * The URL for the step.
     *
     * @var string|null
     */
    private ?string $url = null;

    /**
     * The description of the step.
     *
     * @var string|null
     */
    private ?string $description = null;

    /**
     * The due time for the step in minutes.
     *
     * @var int|null
     */
    private ?int $dueAfterMinutes = null;

    /**
     * Whether the step allows retrying.
     *
     * @var bool
     */
    private bool $allowRetry = true;

    /**
     * The maximum number of attempts for the step.
     *
     * @var int|null
     */
    private ?int $maximumAttempts = null;

    /**
     * The completion condition for the step.
     *
     * @var Closure|class-string<StepCondition>|null
     */
    private Closure|string|null $completionCondition = null;

    /**
     * The visibility condition for the step.
     *
     * @var Closure|class-string<StepCondition>|null
     */
    private Closure|string|null $visibilityCondition = null;

    /**
     * The metadata for the step.
     *
     * @var array<string, mixed>
     */
    private array $metadata = [];

    /**
     * Create a new step definition instance.
     *
     * @param  string  $key  The unique key for the step.
     * @param  int  $position  The position of the step in the passage.
     * @throws InvalidArgumentException If the key is empty.
     * @return void
     */
    public function __construct(
        private readonly string $key,
        private readonly int $position,
    ) {
        // Validate that the key is not empty
        if ($key === '') {
            throw new InvalidArgumentException('A passage step key cannot be empty.');
        }

        // Set the default name for the step based on the key, replacing hyphens and underscores with spaces and capitalizing each word
        $this->name = str($key)->replace(['-', '_'], ' ')->title()->toString();
    }

    /**
     * Set the name of the step.
     *
     * @param  string  $name  The name of the step.
     * @return $this
     */
    public function name(string $name): self
    {
        // Set the name of the step
        $this->name = $name;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the description of the step.
     *
     * @param  string|null  $description  The description of the step.
     * @return $this
     */
    public function description(?string $description): self
    {
        // Set the description of the step
        $this->description = $description;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set whether the step is required or optional.
     *
     * @param  bool  $required  Whether the step is required (true) or optional (false).
     * @return $this
     */
    public function required(bool $required = true): self
    {
        // Set whether the step is required or optional
        $this->required = $required;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the step as optional.
     *
     * @return $this
     */
    public function optional(): self
    {
        // Set the step as optional by calling the required method with false
        return $this->required(false);
    }

    /**
     * Set the prerequisites for the step.
     *
     * @param  string ...$stepKeys  The keys of the prerequisite steps.
     * @return $this
     */
    public function dependsOn(string ...$stepKeys): self
    {
        // Merge the provided step keys with the existing prerequisites, ensuring uniqueness and preserving order
        $this->prerequisites = array_values(array_unique([
            ...$this->prerequisites,
            ...$stepKeys,
        ]));

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the route for the step.
     *
     * @param  string  $route  The name of the route.
     * @param  array<string, scalar|null>  $parameters  The parameters for the route.
     * @return $this
     */
    public function route(string $route, array $parameters = []): self
    {
        // Set the route name and parameters for the step
        $this->route = $route;
        $this->routeParameters = $parameters;
        $this->url = null;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the direct URL for the step.
     *
     * @param  string  $url  The direct URL for the step.
     * @return $this
     */
    public function url(string $url): self
    {
        // Set the direct URL for the step and clear any previously set route information
        $this->url = $url;
        $this->route = null;
        $this->routeParameters = [];

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the due time for the step in minutes.
     *
     * @param  int|null  $minutes  The due time in minutes, or null for no due time.
     * @throws InvalidArgumentException If the provided minutes is less than 1.
     * @return $this
     */
    public function dueAfterMinutes(?int $minutes): self
    {
        // Validate that the provided minutes is at least 1 if it is not null
        if ($minutes !== null && $minutes < 1) {
            throw new InvalidArgumentException('Step due time must be at least one minute.');
        }

        // Set the due time for the step in minutes
        $this->dueAfterMinutes = $minutes;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set whether the step allows retrying and the maximum number of attempts.
     *
     * @param  bool  $allowRetry  Whether retrying is allowed (true) or not (false).
     * @param  int|null  $maximumAttempts  The maximum number of attempts, or null for unlimited attempts.
     * @throws InvalidArgumentException If the provided maximum attempts is less than 1.
     * @return $this
     */
    public function retryable(bool $allowRetry = true, ?int $maximumAttempts = null): self
    {
        // Validate that the provided maximum attempts is at least 1 if it is not null
        if ($maximumAttempts !== null && $maximumAttempts < 1) {
            throw new InvalidArgumentException('Maximum attempts must be at least one.');
        }

        // Set whether the step allows retrying and the maximum number of attempts
        $this->allowRetry = $allowRetry;
        $this->maximumAttempts = $maximumAttempts;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the condition for when the step is considered complete.
     *
     * @param  Closure|class-string<StepCondition>  $condition  The condition for completion.
     * @return $this
     */
    public function completeWhen(Closure|string $condition): self
    {
        // Set the condition for when the step is considered complete
        $this->completionCondition = $condition;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the condition for when the step is considered visible.
     *
     * @param  Closure|class-string<StepCondition>  $condition  The condition for visibility.
     * @return $this
     */
    public function visibleWhen(Closure|string $condition): self
    {
        // Set the condition for when the step is considered visible
        $this->visibilityCondition = $condition;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the metadata for the step.
     *
     * @param  array<string, mixed>  $metadata  The metadata for the step.
     * @return $this
     */
    public function metadata(array $metadata): self
    {
        // Set the metadata for the step
        $this->metadata = $metadata;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Get the unique key for the step.
     *
     * @return string
     */
    public function key(): string
    {
        // Return the unique key for the step
        return $this->key;
    }

    /**
     * Get the position of the step in the passage.
     *
     * @return int
     */
    public function position(): int
    {
        // Return the position of the step in the passage
        return $this->position;
    }
    
    /**
     * Get the label of the step.
     *
     * @return string
     */
    public function label(): string
    {
        // Return the label of the step, which is the same as the name
        return $this->name;
    }

    /**
     * Get the description of the step.
     *
     * @return string|null
     */
    public function details(): ?string
    {
        // Return the description of the step
        return $this->description;
    }

    /**
     * Determine if the step is required.
     *
     * @return bool
     */
    public function isRequired(): bool
    {
        // Return whether the step is required or optional
        return $this->required;
    }

    /**
     * Get the prerequisites for the step.
     *
     * @return list<string>
     */
    public function prerequisites(): array
    {
        // Return the list of prerequisites for the step
        return $this->prerequisites;
    }

    /**
     * Get the route name for the step.
     *
     * @return string|null
     */
    public function routeName(): ?string
    {
        // Return the name of the route for the step
        return $this->route;
    }

    /**
     * Get the route parameters for the step.
     *
     * @return array<string, scalar|null>
     */
    public function routeParameters(): array
    {
        // Return the route parameters for the step
        return $this->routeParameters;
    }
    
    /**
     * Get the direct URL for the step.
     *
     * @return string|null
     */
    public function directUrl(): ?string
    {
        // Return the direct URL for the step
        return $this->url;
    }

    /**
     * Get the due time for the step in minutes.
     *
     * @return int|null
     */
    public function dueMinutes(): ?int
    {
        // Return the due time for the step in minutes
        return $this->dueAfterMinutes;
    }

    /**
     * Determine if the step allows retrying.
     *
     * @return bool
     */
    public function allowsRetry(): bool
    {
        // Return whether the step allows retrying
        return $this->allowRetry;
    }

    /**
     * Get the maximum number of attempts for the step.
     *
     * @return int|null
     */
    public function maxAttempts(): ?int
    {
        // Return the maximum number of attempts for the step, or null if unlimited
        return $this->maximumAttempts;
    }

    /** 
     * Get the condition for when the step is considered complete.
     *
     * @return Closure|class-string<StepCondition>|null
     */
    public function completionCondition(): Closure|string|null
    {
        // Return the condition for when the step is considered complete
        return $this->completionCondition;
    }

    /** 
     * Get the condition for when the step is considered visible.
     *
     * @return Closure|class-string<StepCondition>|null
     */
    public function visibilityCondition(): Closure|string|null
    {
        // Return the condition for when the step is considered visible
        return $this->visibilityCondition;
    }

    /**
     * Get the metadata for the step.
     *
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        // Return the metadata for the step
        return $this->metadata;
    }
}
