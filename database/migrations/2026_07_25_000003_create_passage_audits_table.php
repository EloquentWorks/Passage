<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('passage.tables.audits', 'passage_audits');
        $enrollments = (string) config('passage.tables.enrollments', 'passage_enrollments');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($enrollments): void {
            $table->id();
            $table->foreignId('enrollment_id')->nullable()->constrained($enrollments)->nullOnDelete();
            $table->string('passage_key')->index();
            $table->string('step_key')->nullable()->index();
            $table->string('event')->index();
            $table->string('subject_type');
            $table->string('subject_id');
            $table->string('actor_type')->nullable();
            $table->string('actor_id')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('occurred_at')->index();
            $table->timestamps();

            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'passage_audit_subject_time_index');
            $table->index(['actor_type', 'actor_id', 'occurred_at'], 'passage_audit_actor_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((string) config('passage.tables.audits', 'passage_audits'));
    }
};
