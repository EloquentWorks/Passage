<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Definitions;

use EloquentWorks\Passage\Exceptions\PassageNotDefined;

final class PassageRegistry
{
    /** @var array<string, PassageDefinition> */
    private array $definitions = [];

    public function define(string $key): PassageDefinition
    {
        return $this->definitions[$key] ??= new PassageDefinition($key);
    }

    public function register(PassageDefinition $definition): self
    {
        $this->definitions[$definition->key()] = $definition;

        return $this;
    }

    public function get(string $key): PassageDefinition
    {
        return $this->definitions[$key] ?? throw PassageNotDefined::for($key);
    }

    public function has(string $key): bool
    {
        return isset($this->definitions[$key]);
    }

    /** @return array<string, PassageDefinition> */
    public function all(): array
    {
        return $this->definitions;
    }

    public function forget(string $key): void
    {
        unset($this->definitions[$key]);
    }

    public function flush(): void
    {
        $this->definitions = [];
    }

    /** @param array<string, array<string, mixed>> $definitions */
    public function hydrate(array $definitions): void
    {
        foreach ($definitions as $key => $configuration) {
            $definition = $this->define($key)
                ->name((string) ($configuration['name'] ?? str($key)->headline()))
                ->description(isset($configuration['description']) ? (string) $configuration['description'] : null)
                ->category(isset($configuration['category']) ? (string) $configuration['category'] : null)
                ->version((int) ($configuration['version'] ?? 1))
                ->repeatable((bool) ($configuration['repeatable'] ?? false))
                ->dueAfterMinutes(isset($configuration['due_after_minutes']) ? (int) $configuration['due_after_minutes'] : null)
                ->metadata(is_array($configuration['metadata'] ?? null) ? $configuration['metadata'] : []);

            $tags = $configuration['tags'] ?? [];
            if (is_array($tags)) {
                $definition->tags(...array_values(array_filter($tags, 'is_string')));
            }

            $steps = $configuration['steps'] ?? [];
            if (! is_array($steps)) {
                continue;
            }

            foreach ($steps as $stepKey => $stepConfiguration) {
                if (! is_array($stepConfiguration)) {
                    continue;
                }

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

                    $dependencies = $stepConfiguration['depends_on'] ?? [];
                    if (is_array($dependencies)) {
                        $step->dependsOn(...array_values(array_filter($dependencies, 'is_string')));
                    }

                    if (isset($stepConfiguration['route']) && is_string($stepConfiguration['route'])) {
                        $parameters = is_array($stepConfiguration['route_parameters'] ?? null)
                            ? $stepConfiguration['route_parameters']
                            : [];
                        $step->route($stepConfiguration['route'], $parameters);
                    } elseif (isset($stepConfiguration['url']) && is_string($stepConfiguration['url'])) {
                        $step->url($stepConfiguration['url']);
                    }

                    if (isset($stepConfiguration['complete_when']) && is_string($stepConfiguration['complete_when'])) {
                        $step->completeWhen($stepConfiguration['complete_when']);
                    }

                    if (isset($stepConfiguration['visible_when']) && is_string($stepConfiguration['visible_when'])) {
                        $step->visibleWhen($stepConfiguration['visible_when']);
                    }
                });
            }
        }
    }
}
