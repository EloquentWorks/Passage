<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Get the table name from the configuration, defaulting to 'passage_step_progress'
        $tableName = (string) config('passage.tables.steps', 'passage_step_progress');
        $enrollments = (string) config('passage.tables.enrollments', 'passage_enrollments');

        // Check if the table already exists to avoid errors
        if (Schema::hasTable($tableName)) {
            return;
        }

        // Create the table with the specified schema
        Schema::create($tableName, function (Blueprint $table) use ($enrollments): void {
            // Add an auto-incrementing primary key
            $table->id();

            // Add a foreign key constraint for enrollment_id referencing the enrollments table
            $table->foreignId('enrollment_id')->constrained($enrollments)->cascadeOnDelete();

            // Add a string column for step_key, which can store the unique key of the step
            $table->string('step_key');

            // Add an unsigned integer column for position, which can store the order of the step in the passage
            $table->unsignedInteger('position');

            // Add a boolean column for required, which can store whether the step is required or optional
            $table->boolean('required')->default(true)->index();
            
            // Add a string column for state, which can store the current state of the step (e.g., pending, completed, failed)
            $table->string('state')->index();

            // Add an unsigned integer column for attempts, which can store the number of attempts made for the step
            $table->unsignedInteger('attempts')->default(0);

            // Add a nullable text column for failure_reason, which can store the reason for failure if the step fails
            $table->text('failure_reason')->nullable();

            // Add a JSON column for data, which can store additional information about the step progress
            $table->json('data')->nullable();

            // Add timestamps for various states of the step
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('skipped_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();

            // Add timestamps for created_at and updated_at
            $table->timestamps();

            // Add a unique constraint for enrollment_id and step_key to ensure that each step is unique per enrollment
            $table->unique(['enrollment_id', 'step_key'], 'passage_enrollment_step_unique');

            // Add a composite index for enrollment_id and position for faster lookups
            $table->index(['enrollment_id', 'position'], 'passage_enrollment_position_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the table if it exists
        Schema::dropIfExists((string) config('passage.tables.steps', 'passage_step_progress'));
    }
};
