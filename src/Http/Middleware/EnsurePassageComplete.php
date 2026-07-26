<?php

declare(strict_types=1);

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
    public function __construct(
        private PassageManager $manager,
        private PassageRegistry $registry,
    ) {}

    public function handle(Request $request, Closure $next, string $passage): Response
    {
        $subject = $request->user();

        if (! $subject instanceof Model) {
            return $next($request);
        }

        $enrollment = $this->manager->active($subject, $passage);

        if ($enrollment === null && (bool) config('passage.middleware.auto_enroll', true)) {
            $enrollment = $this->manager->enroll($subject, $passage);
        }

        if ($enrollment === null || $this->manager->sync($subject, $passage)->isComplete()) {
            return $next($request);
        }

        $snapshot = $this->manager->progress($subject, $passage);
        $stepProgress = $this->manager->nextStep($subject, $passage);
        $step = $stepProgress !== null
            ? $this->registry->get($passage)->stepDefinition($stepProgress->step_key)
            : null;

        if ($request->expectsJson()) {
            return new JsonResponse([
                'message' => 'This passage must be completed before continuing.',
                'passage' => $snapshot->toArray(),
            ], (int) config('passage.middleware.json_status', 409));
        }

        if ($step?->routeName() !== null) {
            return new RedirectResponse(route($step->routeName(), $step->routeParameters()));
        }

        if ($step?->directUrl() !== null) {
            return new RedirectResponse($step->directUrl());
        }

        $fallbackRoute = config('passage.middleware.incomplete_route');
        if (is_string($fallbackRoute) && $fallbackRoute !== '') {
            return new RedirectResponse(route($fallbackRoute, ['passage' => $passage]));
        }

        abort(409, 'Passage incomplete and no redirect target is configured.');
    }
}
