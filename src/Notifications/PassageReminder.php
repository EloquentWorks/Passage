<?php

declare(strict_types=1);

namespace EloquentWorks\Passage\Notifications;

use EloquentWorks\Passage\Definitions\PassageRegistry;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\PassageManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class PassageReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly PassageEnrollment $enrollment) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        $channels = config('passage.reminders.channels', ['mail']);

        return is_array($channels) ? array_values(array_filter($channels, 'is_string')) : ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $manager = app(PassageManager::class);
        $registry = app(PassageRegistry::class);
        $subject = $this->enrollment->subject;
        $next = $manager->nextStep($subject, $this->enrollment->passage_key);
        $definition = $registry->get($this->enrollment->passage_key);
        $step = $next !== null ? $definition->stepDefinition($next->step_key) : null;

        $message = (new MailMessage)
            ->subject("Continue {$definition->label()}")
            ->greeting('Your next step is ready')
            ->line("You still have progress remaining in {$definition->label()}.");

        if ($step !== null) {
            $message->line("Next: {$step->label()}");

            if ($step->routeName() !== null) {
                $message->action('Continue', route($step->routeName(), $step->routeParameters()));
            } elseif ($step->directUrl() !== null) {
                $message->action('Continue', $step->directUrl());
            }
        }

        return $message;
    }

    /** @return array<string, int|string|null> */
    public function toArray(object $notifiable): array
    {
        return [
            'enrollment_id' => $this->enrollment->getKey(),
            'passage' => $this->enrollment->passage_key,
            'version' => $this->enrollment->passage_version,
            'cycle' => $this->enrollment->cycle,
            'due_at' => $this->enrollment->due_at?->toISOString(),
        ];
    }
}
