<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tableName = (string) config('passage.tables.steps', 'passage_step_progress');
        $enrollments = (string) config('passage.tables.enrollments', 'passage_enrollments');

        if (Schema::hasTable($tableName)) {
            return;
        }

        Schema::create($tableName, function (Blueprint $table) use ($enrollments): void {
            $table->id();
            $table->foreignId('enrollment_id')->constrained($enrollments)->cascadeOnDelete();
            $table->string('step_key');
            $table->unsignedInteger('position');
            $table->boolean('required')->default(true)->index();
            $table->string('state')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('failure_reason')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['enrollment_id', 'step_key'], 'passage_enrollment_step_unique');
            $table->index(['enrollment_id', 'position'], 'passage_enrollment_position_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists((string) config('passage.tables.steps', 'passage_step_progress'));
    }
};
