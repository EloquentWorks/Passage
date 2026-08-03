<?php

namespace EloquentWorks\Passage\Definitions;

use Closure;
use InvalidArgumentException;

final class PassageDefinition
{
    /**
     * The unique key for the passage.
     *
     * @var string
     */
    private string $name;

    /**
     * The version of the passage.
     *
     * @var int
     */
    private int $version = 1;

    /**
     * A brief description of the passage.
     *
     * @var string|null
     */
    private ?string $description = null;

    /**
     * The category of the passage.
     *
     * @var string|null
     */
    private ?string $category = null;

    /**
     * Whether the passage is repeatable.
     *
     * @var bool
     */
    private bool $repeatable = false;

    /**
     * The number of minutes after which the passage is due.
     *
     * @var int|null
     */
    private ?int $dueAfterMinutes = null;

    /**
     * The tags associated with the passage.
     *
     * @var list<string>
     */
    private array $tags = [];

    /**
     * The metadata associated with the passage.
     *
     * @var array<string, mixed>
     */
    private array $metadata = [];

    /**
     * The steps associated with the passage.
     *
     * @var array<string, StepDefinition>
     */
    private array $steps = [];

    /**
     * Create a new passage definition.
     *
     * @param  string  $key  The unique key for the passage.
     * @throws InvalidArgumentException If the key is empty.
     * @return void
     */
    public function __construct(private readonly string $key)
    {
        // Validate the key to ensure it is not empty
        if ($key === '') {
            throw new InvalidArgumentException('A passage key cannot be empty.');
        }

        // Set the name of the passage by transforming the key into a title format
        $this->name = str($key)->replace(['-', '_'], ' ')->title()->toString();
    }

    /**
     * Set the name of the passage.
     *
     * @param  string  $name  The name of the passage.
     * @return self
     */
    public function name(string $name): self
    {
        // Set the name of the passage
        $this->name = $name;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the description of the passage.
     *
     * @param  string|null  $description  The description of the passage.
     * @return self
     */
    public function description(?string $description): self
    {
        // Set the description of the passage
        $this->description = $description;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the category of the passage.
     *
     * @param  string|null  $category  The category of the passage.
     * @return self
     */
    public function category(?string $category): self
    {
        // Set the category of the passage
        $this->category = $category;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the version of the passage.
     *
     * @param  int  $version  The version of the passage.
     * @throws InvalidArgumentException If the version is less than one.
     * @return self
     */
    public function version(int $version): self
    {
        // Validate the version to ensure it is at least one
        if ($version < 1) {
            throw new InvalidArgumentException('A passage version must be at least one.');
        }

        // Set the version of the passage
        $this->version = $version;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set whether the passage is repeatable.
     *
     * @param  bool  $repeatable  Whether the passage is repeatable.
     * @return self
     */
    public function repeatable(bool $repeatable = true): self
    {
        // Set whether the passage is repeatable
        $this->repeatable = $repeatable;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the number of minutes after which the passage is due.
     *
     * @param  int|null  $minutes  The number of minutes after which the passage is due.
     * @throws InvalidArgumentException If the number of minutes is less than one.
     * @return self
     */
    public function dueAfterMinutes(?int $minutes): self
    {
        // Validate the number of minutes to ensure it is at least one if provided
        if ($minutes !== null && $minutes < 1) {
            throw new InvalidArgumentException('Passage due time must be at least one minute.');
        }
        
        // Set the number of minutes after which the passage is due
        $this->dueAfterMinutes = $minutes;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the tags of the passage.
     *
     * @param  string  ...$tags  The tags to associate with the passage.
     * @return self
     */
    public function tags(string ...$tags): self
    {
        // Set the tags of the passage, ensuring they are unique
        $this->tags = array_values(array_unique([...$this->tags, ...$tags]));

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Set the metadata of the passage.
     *
     * @param  array<string, mixed>  $metadata  The metadata to associate with the passage.
     * @return self
     */
    public function metadata(array $metadata): self
    {
        // Set the metadata of the passage
        $this->metadata = $metadata;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Define a step for the passage.
     *
     * @param  string  $key  The unique key for the step.
     * @param  Closure|null  $configure  An optional closure to configure the step.
     * @throws InvalidArgumentException If the step key is already registered.
     * @return self
     */
    public function step(string $key, ?Closure $configure = null): self
    {
        // Check if the step key is already registered for this passage
        if (isset($this->steps[$key])) {
            throw new InvalidArgumentException("The [{$key}] step is already registered for [{$this->key}].");
        }

        // Create a new StepDefinition instance for the step
        $step = new StepDefinition($key, count($this->steps) + 1);
        if ($configure !== null) {
            $configure($step);
        }
        $this->steps[$key] = $step;

        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Add a step definition to the passage.
     *
     * @param  StepDefinition  $step  The step definition to add.
     * @throws InvalidArgumentException If the step key is already registered.
     * @return self
     */
    public function addStep(StepDefinition $step): self
    {
        // Check if the step key is already registered for this passage
        if (isset($this->steps[$step->key()])) {
            throw new InvalidArgumentException("The [{$step->key()}] step is already registered for [{$this->key}].");
        }

        // Add the step definition to the passage
        $this->steps[$step->key()] = $step;
        
        // Return the current instance for method chaining
        return $this;
    }

    /**
     * Get the unique key of the passage.
     *
     * @return string
     */
    public function key(): string
    {
        // Return the unique key of the passage
        return $this->key;
    }

    /**
     * Get the label of the passage.
     *
     * @return string
     */
    public function label(): string
    {
        // Return the name of the passage as its label
        return $this->name;
    }

    /**
     * Get the details of the passage.
     *
     * @return string|null
     */
    public function details(): ?string
    {
        // Return the description of the passage
        return $this->description;
    }

    /**
     * Get the group of the passage.
     *
     * @return string|null
     */
    public function group(): ?string
    {
        // Return the category of the passage
        return $this->category;
    }

    /**
     * Get the revision number of the passage.
     *
     * @return int
     */
    public function revision(): int
    {
        // Return the revision number of the passage
        return $this->version;
    }
    
    /**
     * Determine if the passage is repeatable.
     *
     * @return bool
     */
    public function isRepeatable(): bool
    {
        // Return whether the passage is repeatable
        return $this->repeatable;
    }

    /**
     * Get the number of minutes after which the passage is due.
     *
     * @return int|null
     */
    public function dueMinutes(): ?int
    {
        // Return the number of minutes after which the passage is due
        return $this->dueAfterMinutes;
    }

    /**
     * Get the list of tags associated with the passage.
     *
     * @return list<string>
     */
    public function tagList(): array
    {
        // Return the list of tags associated with the passage
        return $this->tags;
    }

    /**
     * Get the metadata associated with the passage.
     *
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        // Return the metadata associated with the passage
        return $this->metadata;
    }

    /**
     * Get the list of step definitions associated with the passage.
     *
     * @return array<string, StepDefinition>
     */
    public function steps(): array
    {
        // Return the list of step definitions associated with the passage
        return $this->steps;
    }

    /**
     * Get a specific step definition by its key.
     *
     * @param  string  $key  The unique key of the step.
     * @throws InvalidArgumentException If the step key is not defined.
     * @return StepDefinition
     */
    public function stepDefinition(string $key): StepDefinition
    {
        // Return the step definition if it exists, otherwise throw an exception
        return $this->steps[$key]
            ?? throw new InvalidArgumentException("The [{$key}] step is not defined for [{$this->key}].");
    }
}
