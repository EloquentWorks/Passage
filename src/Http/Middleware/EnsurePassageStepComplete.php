<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Http\Middleware;

use Closure;
use EloquentWorks\Passage\Enums\StepState;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsurePassageStepComplete
{
    public function __construct(private PassageManager $manager) {}

    public function handle(Request $request, Closure $next, string $passage, string $step): Response
    {
        $subject = $request->user();

        if (! $subject instanceof Model) {
            return $next($request);
        }

        $enrollment = $this->manager->getOrEnroll($subject, $passage);
        $complete = $enrollment->steps()
            ->where('step_key', $step)
            ->whereIn('state', [StepState::Completed->value, StepState::Skipped->value])
            ->exists();

        abort_unless($complete, 409, "The [{$step}] passage step must be completed first.");

        return $next($request);
    }
}
