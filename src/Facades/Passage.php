<?php

namespace EloquentWorks\Passage\Facades;

use EloquentWorks\Passage\Definitions\PassageDefinition;
use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Support\Facades\Facade;

/**
 * @method static PassageRegistry registry()
 * @method static PassageDefinition definition(string $key)
 * @method static \EloquentWorks\Passage\Models\PassageEnrollment enroll(\Illuminate\Database\Eloquent\Model $subject, string $passage, array<string, mixed> $metadata = [], bool $forceNew = false, ?\Illuminate\Database\Eloquent\Model $actor = null)
 * @method static \EloquentWorks\Passage\Models\PassageEnrollment|null current(\Illuminate\Database\Eloquent\Model $subject, string $passage)
 * @method static \EloquentWorks\Passage\Models\PassageEnrollment|null active(\Illuminate\Database\Eloquent\Model $subject, string $passage)
 * @method static \EloquentWorks\Passage\Models\PassageEnrollment sync(\Illuminate\Database\Eloquent\Model $subject, string $passage)
 * @method static \EloquentWorks\Passage\Data\ProgressSnapshot progress(\Illuminate\Database\Eloquent\Model $subject, string $passage)
 * @method static \EloquentWorks\Passage\Models\PassageStepProgress|null nextStep(\Illuminate\Database\Eloquent\Model $subject, string $passage)
 * @method static \EloquentWorks\Passage\Models\PassageStepProgress completeStep(\Illuminate\Database\Eloquent\Model $subject, string $passage, string $step, array<string, mixed> $data = [], ?\Illuminate\Database\Eloquent\Model $actor = null, bool $force = false)
 * @method static \EloquentWorks\Passage\Models\PassageStepProgress skipStep(\Illuminate\Database\Eloquent\Model $subject, string $passage, string $step, array<string, mixed> $data = [], ?\Illuminate\Database\Eloquent\Model $actor = null, bool $force = false)
 * @method static \EloquentWorks\Passage\Models\PassageStepProgress failStep(\Illuminate\Database\Eloquent\Model $subject, string $passage, string $step, string $reason, array<string, mixed> $data = [], ?\Illuminate\Database\Eloquent\Model $actor = null)
 * @method static \EloquentWorks\Passage\Models\PassageEnrollment restart(\Illuminate\Database\Eloquent\Model $subject, string $passage, array<string, mixed> $metadata = [], ?\Illuminate\Database\Eloquent\Model $actor = null)
 */
final class Passage extends Facade
{
    /**
     * Get the passage definition for the given key.
     *
     * @param  string  $key  The key of the passage definition to retrieve.
     * @throws \EloquentWorks\Passage\Exceptions\PassageDefinitionNotFoundException
     * @return PassageDefinition
     */
    public static function define(string $key): PassageDefinition
    {
        /** @var PassageManager $manager */
        $manager = static::getFacadeRoot();

        // If the definition is not found, throw an exception
        return $manager->registry()->define($key);
    }

    /**
     * Get the underlying PassageManager instance.
     *
     * @return PassageManager
     */
    protected static function getFacadeAccessor(): string
    {
        // Return the class name of the PassageManager to resolve it from the service container
        return PassageManager::class;
    }
}
