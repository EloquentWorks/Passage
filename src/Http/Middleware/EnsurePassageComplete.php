<?php

namespace EloquentWorks\Passage\Http\Middleware;

use Closure;
use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsurePassageComplete
{
    /**
     * Create a new middleware instance.
     *
     * @param  PassageManager  $manager  The passage manager instance.
     * @param  PassageRegistry  $registry  The passage registry instance.
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
     * @param  string  $passage  The passage key to check for completion.
     * @return Response
     */
    public function handle(Request $request, Closure $next, string $passage): Response
    {
        // If the user is not authenticated, we cannot check for passage completion.
        $subject = $request->user();

        // If the subject is not a model, we cannot check for passage completion.
        if (! $subject instanceof Model) {
            return $next($request);
        }

        // If the passage is not registered, we cannot check for passage completion.
        $enrollment = $this->manager->active($subject, $passage);

        // If the subject is not enrolled in the passage, we can optionally auto-enroll them.
        if ($enrollment === null && (bool) config('passage.middleware.auto_enroll', true)) {
            $enrollment = $this->manager->enroll($subject, $passage);
        }

        // If the subject is not enrolled in the passage, or if the passage is complete, we can allow the request to proceed.
        if ($enrollment === null || $this->manager->sync($subject, $passage)->isComplete()) {
            return $next($request);
        }

        // If the subject is enrolled in the passage, but the passage is not complete, we can redirect
        // them to the next step in the passage.
        $snapshot = $this->manager->progress($subject, $passage);
        $stepProgress = $this->manager->nextStep($subject, $passage);
        $step = $stepProgress !== null
            ? $this->registry->get($passage)->stepDefinition($stepProgress->step_key)
            : null;

        // If the request expects a JSON response, we can return a JSON response with the passage snapshot.
        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'This passage must be completed before continuing.',
                'passage' => $snapshot->toArray(),
            ], (int) config('passage.middleware.json_status', 409));
        }

        // If the next step has a route name, we can redirect the user to that route.
        if ($step?->routeName() !== null) {
            return new RedirectResponse(route($step->routeName(), $step->routeParameters()));
        }

        // If the next step has a direct URL, we can redirect the user to that URL.
        if ($step?->directUrl() !== null) {
            return new RedirectResponse($step->directUrl());
        }

        // If the next step does not have a route name or direct URL, we can redirect the user
        // to a fallback route if one is configured.
        $fallbackRoute = config('passage.middleware.incomplete_route');
        if (is_string($fallbackRoute) && $fallbackRoute !== '') {
            return new RedirectResponse(route($fallbackRoute, ['passage' => $passage]));
        }

        // If no redirect target is configured, we can abort with a 409 Conflict response.
        abort(409, 'Passage incomplete and no redirect target is configured.');
    }
}
