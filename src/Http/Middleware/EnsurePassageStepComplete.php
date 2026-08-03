<?php

namespace EloquentWorks\Passage\Http\Middleware;

use Closure;
use EloquentWorks\Passage\Enums\StepState;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsurePassageStepComplete
{
    /**
     * Create a new middleware instance.
     *
     * @param  PassageManager  $manager  The PassageManager instance.
     * @return void
     */
    public function __construct(private PassageManager $manager) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request
     * @param  Closure  $next
     * @param  string  $passage
     * @param  string  $step
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $passage, string $step): Response
    {
        // Get the subject (user) from the request
        $subject = $request->user();

        // If the subject is not a model, we cannot check for passage enrollment, so we just pass the request through
        if (! $subject instanceof Model) {
            return $next($request);
        }

        // Check if the subject is enrolled in the passage and if the step is completed
        $enrollment = $this->manager->getOrEnroll($subject, $passage);
        $complete = $enrollment->steps()
            ->where('step_key', $step)
            ->whereIn('state', [StepState::Completed->value, StepState::Skipped->value])
            ->exists();

        // If the step is not completed, abort the request with a 409 Conflict response
        abort_unless($complete, 409, "The [{$step}] passage step must be completed first.");

        // If the step is completed, pass the request to the next middleware or controller
        return $next($request);
    }
}
