<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('passage.tables.enrollments', 'passage_enrollments');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('subject_type');
            $table->string('subject_id');
            $table->string('passage_key');
            $table->unsignedInteger('passage_version')->default(1);
            $table->unsignedInteger('cycle')->default(1);
            $table->string('state')->index();
            $table->json('metadata')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['subject_type', 'subject_id', 'passage_key', 'cycle'],
                'passage_subject_key_cycle_unique'
            );
            $table->index(
                ['subject_type', 'subject_id', 'passage_key', 'state'],
                'passage_subject_key_state_index'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((string) config('passage.tables.enrollments', 'passage_enrollments'));
    }
};
