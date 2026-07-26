<?php

declare(strict_types=1);

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

        $this->manager->sync($subject, $passage);
        $progress = $this->manager->nextStep($subject, $passage);

        if ($progress === null) {
            return $next($request);
        }

        $step = $this->registry->get($passage)->stepDefinition($progress->step_key);

        if ($step->routeName() !== null) {
            return new RedirectResponse(route($step->routeName(), $step->routeParameters()));
        }

        if ($step->directUrl() !== null) {
            return new RedirectResponse($step->directUrl());
        }

        return $next($request);
    }
}
