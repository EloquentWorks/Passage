<?php

namespace EloquentWorks\Passage\Http\Middleware;

use Closure;
use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RedirectToNextPassageStep
{
    /**
     * Create a new middleware instance.
     *
     * @param  PassageManager  $manager  The PassageManager instance.
     * @param  PassageRegistry  $registry  The PassageRegistry instance.
     * @return void
     */
    public function __construct(
        private PassageManager $manager,
        private PassageRegistry $registry,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Request  $request  The incoming request.
     * @param  Closure  $next  The next middleware in the stack.
     * @param  string  $passage  The passage key to check for progress.
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $passage): Response
    {
        // If the user is not authenticated, we cannot check their progress, so we just pass the request to the next middleware.
        $subject = $request->user();

        // If the subject is not a model, we cannot check their progress, so we just pass the request to the next middleware.
        if (! $subject instanceof Model) {
            return $next($request);
        }

        // Sync the subject's progress for the passage and get the next step.
        $this->manager->sync($subject, $passage);
        $progress = $this->manager->nextStep($subject, $passage);

        // If there is no next step, we just pass the request to the next middleware.
        if ($progress === null) {
            return $next($request);
        }

        // Get the step definition for the next step.
        $step = $this->registry->get($passage)->stepDefinition($progress->step_key);

        // If the step has a route name, redirect to that route.
        if ($step->routeName() !== null) {
            return new RedirectResponse(route($step->routeName(), $step->routeParameters()));
        }

        // If the step has a direct URL, redirect to that URL.
        if ($step->directUrl() !== null) {
            return new RedirectResponse($step->directUrl());
        }

        // If the step does not have a route name or direct URL, pass the request to the next middleware.
        return $next($request);
    }
}
