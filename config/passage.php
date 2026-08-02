<?php

use EloquentWorks\Passage\Models\PassageAudit;
use EloquentWorks\Passage\Models\PassageEnrollment;
use EloquentWorks\Passage\Models\PassageStepProgress;

return [

    /*
    |--------------------------------------------------------------------------
    | Database tables and models
    |--------------------------------------------------------------------------
    |
    | You may specify the database tables and models used by Passage. If you
    | need to customize the models, you may extend the default models and
    | specify your own model classes here.
    |
    */

    'tables' => [
        'enrollments' => 'passage_enrollments',
        'steps' => 'passage_step_progress',
        'audits' => 'passage_audits',
    ],

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | You may specify the models used by Passage. If you need to customize
    | the models, you may extend the default models and specify your own
    | model classes here.
    |
    */

    'models' => [
        'enrollment' => PassageEnrollment::class,
        'step' => PassageStepProgress::class,
        'audit' => PassageAudit::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | You may specify the middleware used by Passage. If you need to customize
    | the middleware, you may extend the default middleware and specify your own
    | middleware classes here.
    |
    */

    'middleware' => [
        'auto_enroll' => true,
        'incomplete_route' => null,
        'json_status' => 409,
    ],

    /*
    |--------------------------------------------------------------------------
    | Reminders
    |--------------------------------------------------------------------------
    |
    | You may specify the reminder settings used by Passage. If you need to customize
    | the reminder settings, you may extend the default settings and specify your own
    | settings here.
    |
    */

    'reminders' => [
        'enabled' => true,
        'look_ahead_minutes' => 1440,
        'cooldown_minutes' => 1440,
        'channels' => ['mail'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Pruning
    |--------------------------------------------------------------------------
    |
    | You may specify the pruning settings used by Passage. If you need to customize
    | the pruning settings, you may extend the default settings and specify your own
    | settings here.
    |
    */

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
