<?php

declare(strict_types=1);

return [
    'tables' => [
        'enrollments' => 'passage_enrollments',
        'steps' => 'passage_step_progress',
        'audits' => 'passage_audits',
    ],

    'models' => [
        'enrollment' => EloquentWorks\Passage\Models\PassageEnrollment::class,
        'step' => EloquentWorks\Passage\Models\PassageStepProgress::class,
        'audit' => EloquentWorks\Passage\Models\PassageAudit::class,
    ],

    'middleware' => [
        'auto_enroll' => true,
        'incomplete_route' => null,
        'json_status' => 409,
    ],

    'reminders' => [
        'enabled' => true,
        'look_ahead_minutes' => 1440,
        'cooldown_minutes' => 1440,
        'channels' => ['mail'],
    ],

    'pruning' => [
        'enabled' => false,
        'retention_days' => 365,
        'audit_retention_days' => 730,
    ],

    /*
    |--------------------------------------------------------------------------
    | Config-defined passages
    |--------------------------------------------------------------------------
    |
    | Definitions may also be registered fluently from an application service
    | provider. Class-string conditions must implement StepCondition.
    |
    */
    'definitions' => [
        // 'account-setup' => [
        //     'name' => 'Account setup',
        //     'version' => 1,
        //     'category' => 'onboarding',
        //     'repeatable' => false,
        //     'due_after_minutes' => 10080,
        //     'steps' => [
        //         'verify-email' => [
        //             'name' => 'Verify your email',
        //             'required' => true,
        //             'route' => 'verification.notice',
        //         ],
        //     ],
        // ],
    ],
];
