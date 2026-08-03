<?php

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

    /**
     * Create a new notification instance.
     *
     * @param  PassageEnrollment  $enrollment  The enrollment for which the reminder is being sent.
     * @return void
     */
    public function __construct(public readonly PassageEnrollment $enrollment) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        // Get the channels from the configuration, defaulting to ['mail'] if not set
        $channels = config('passage.reminders.channels', ['mail']);

        // Filter the channels to ensure they are strings and return them as a list
        return is_array($channels) ? array_values(array_filter($channels, 'is_string')) : ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Retrieve the PassageManager and PassageRegistry instances from the application container
        $manager = app(PassageManager::class);
        $registry = app(PassageRegistry::class);
        $subject = $this->enrollment->subject;
        $next = $manager->nextStep($subject, $this->enrollment->passage_key);
        $definition = $registry->get($this->enrollment->passage_key);
        $step = $next !== null ? $definition->stepDefinition($next->step_key) : null;

        // Build the mail message with subject, greeting, and lines
        $message = (new MailMessage)
            ->subject("Continue {$definition->label()}")
            ->greeting('Your next step is ready')
            ->line("You still have progress remaining in {$definition->label()}.");

        // If there is a next step, add its label and a continue action
        if ($step !== null) {
            $message->line("Next: {$step->label()}");

            // Add a continue action based on the step's route name or direct URL
            if ($step->routeName() !== null) {
                $message->action('Continue', route($step->routeName(), $step->routeParameters()));
            } elseif ($step->directUrl() !== null) {
                $message->action('Continue', $step->directUrl());
            }
        }

        // Add a line indicating when the passage is due, if applicable
        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Convert the PassageReminder notification to an associative array representation.
        return [
            'enrollment_id' => $this->enrollment->getKey(),
            'passage' => $this->enrollment->passage_key,
            'version' => $this->enrollment->passage_version,
            'cycle' => $this->enrollment->cycle,
            'due_at' => $this->enrollment->due_at?->toISOString(),
        ];
    }
}
