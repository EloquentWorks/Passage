<?php

namespace EloquentWorks\Passage\Definitions;

use EloquentWorks\Passage\Exceptions\PassageNotDefined;

final class PassageRegistry
{
    /**
     * The registry of passage definitions.
     *
     * @var array<string, PassageDefinition> An associative array of passage definitions keyed by their unique keys.
     */
    private array $definitions = [];

    /**
     * Define a new passage definition or retrieve an existing one by its key.
     *
     * @param  string  $key  The unique key of the passage definition.
     * @return PassageDefinition The passage definition instance.
     */
    public function define(string $key): PassageDefinition
    {
        // If the passage definition with the given key does not exist, create a new one and store it in the registry
        return $this->definitions[$key] ??= new PassageDefinition($key);
    }

    /**
     * Register a passage definition in the registry.
     *
     * @param  PassageDefinition  $definition  The passage definition to register.
     * @return self The registry instance for method chaining.
     */
    public function register(PassageDefinition $definition): self
    {
        // Register the passage definition in the registry using its unique key
        $this->definitions[$definition->key()] = $definition;

        // Return the registry instance for method chaining
        return $this;
    }

    /**
     * Retrieve a registered passage definition by its key.
     *
     * @param  string  $key  The unique key of the passage definition to retrieve.
     * @return PassageDefinition The registered passage definition.
     *
     * @throws PassageNotDefined If the passage definition is not registered.
     */
    public function get(string $key): PassageDefinition
    {
        // Retrieve the passage definition from the registry or throw an exception if not found
        return $this->definitions[$key] ?? throw PassageNotDefined::for($key);
    }

    /**
     * Check if a passage definition is registered by its key.
     *
     * @param  string  $key  The unique key of the passage definition to check.
     * @return bool True if the passage definition is registered, false otherwise.
     */
    public function has(string $key): bool
    {
        // Check if the passage definition exists in the registry
        return isset($this->definitions[$key]);
    }

    /**
     * Retrieve all registered passage definitions.
     *
     * @return array<string, PassageDefinition> An associative array of all registered passage definitions.
     */
    public function all(): array
    {
        // Return all registered passage definitions
        return $this->definitions;
    }

    /**
     * Remove a registered passage definition by its key.
     *
     * @param  string  $key  The unique key of the passage definition to remove.
     */
    public function forget(string $key): void
    {
        // Remove the passage definition from the registry if it exists
        unset($this->definitions[$key]);
    }

    /**
     * Clear all registered passage definitions.
     */
    public function flush(): void
    {
        // Clear all registered passage definitions
        $this->definitions = [];
    }

    /**
     * Hydrate the registry with passage definitions from an array.
     *
     * @param  array<string, array>  $definitions  An associative array of passage definitions to hydrate the registry with.
     */
    public function hydrate(array $definitions): void
    {
        // Iterate through the provided definitions and register each one in the registry
        foreach ($definitions as $key => $configuration) {
            $definition = $this->define($key)
                ->name((string) ($configuration['name'] ?? str($key)->headline()))
                ->description(isset($configuration['description']) ? (string) $configuration['description'] : null)
                ->category(isset($configuration['category']) ? (string) $configuration['category'] : null)
                ->version((int) ($configuration['version'] ?? 1))
                ->repeatable((bool) ($configuration['repeatable'] ?? false))
                ->dueAfterMinutes(isset($configuration['due_after_minutes']) ? (int) $configuration['due_after_minutes'] : null)
                ->metadata(is_array($configuration['metadata'] ?? null) ? $configuration['metadata'] : []);

            // Add tags to the passage definition if they are provided and are an array of strings
            $tags = $configuration['tags'] ?? [];
            if (is_array($tags)) {
                $definition->tags(...array_values(array_filter($tags, 'is_string')));
            }

            // Add steps to the passage definition if they are provided and are an array of step configurations
            $steps = $configuration['steps'] ?? [];
            if (! is_array($steps)) {
                continue;
            }

            // Iterate through the provided steps and register each one in the passage definition
            foreach ($steps as $stepKey => $stepConfiguration) {
                if (! is_array($stepConfiguration)) {
                    continue;
                }

                // Register the step in the passage definition with its configuration
                $definition->step((string) $stepKey, function (StepDefinition $step) use ($stepConfiguration): void {
                    $step
                        ->name((string) ($stepConfiguration['name'] ?? str($step->key())->headline()))
                        ->description(isset($stepConfiguration['description']) ? (string) $stepConfiguration['description'] : null)
                        ->required((bool) ($stepConfiguration['required'] ?? true))
                        ->dueAfterMinutes(isset($stepConfiguration['due_after_minutes']) ? (int) $stepConfiguration['due_after_minutes'] : null)
                        ->retryable(
                            (bool) ($stepConfiguration['retryable'] ?? true),
                            isset($stepConfiguration['maximum_attempts']) ? (int) $stepConfiguration['maximum_attempts'] : null,
                        )
                        ->metadata(is_array($stepConfiguration['metadata'] ?? null) ? $stepConfiguration['metadata'] : []);

                    // Add dependencies to the step if they are provided and are an array of strings
                    $dependencies = $stepConfiguration['depends_on'] ?? [];
                    if (is_array($dependencies)) {
                        $step->dependsOn(...array_values(array_filter($dependencies, 'is_string')));
                    }

                    // Set the route or URL for the step if provided
                    if (isset($stepConfiguration['route']) && is_string($stepConfiguration['route'])) {
                        $parameters = is_array($stepConfiguration['route_parameters'] ?? null)
                            ? $stepConfiguration['route_parameters']
                            : [];
                        $step->route($stepConfiguration['route'], $parameters);
                    } elseif (isset($stepConfiguration['url']) && is_string($stepConfiguration['url'])) {
                        $step->url($stepConfiguration['url']);
                    }

                    // Set the completion condition for the step if provided
                    if (isset($stepConfiguration['complete_when']) && is_string($stepConfiguration['complete_when'])) {
                        $step->completeWhen($stepConfiguration['complete_when']);
                    }

                    // Set the visibility condition for the step if provided
                    if (isset($stepConfiguration['visible_when']) && is_string($stepConfiguration['visible_when'])) {
                        $step->visibleWhen($stepConfiguration['visible_when']);
                    }
                });
            }
        }
    }
}
