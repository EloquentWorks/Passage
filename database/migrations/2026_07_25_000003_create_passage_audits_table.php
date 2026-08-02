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
        // Get the table name from the configuration, defaulting to 'passage_audits'
        $tableName = (string) config('passage.tables.audits', 'passage_audits');
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
            $table->foreignId('enrollment_id')->nullable()->constrained($enrollments)->nullOnDelete();

            // Add a string column for passage_key, which can store the unique key of the passage associated with the event
            // This column is indexed for efficient querying
            $table->string('passage_key')->index();

            // Add a string column for step_key, which can store the unique key of the step associated with the event
            // This column is nullable because not all events may be associated with a specific step
            $table->string('step_key')->nullable()->index();

            // Add a string column for event, which can store the type of event (e.g., 'step_completed', 'enrollment_started')
            $table->string('event')->index();

            // Add polymorphic relationship columns for subject_type and subject_id, which can store the type and ID
            // of the subject associated with the event
            $table->string('subject_type');
            $table->string('subject_id');

            // Add polymorphic relationship columns for actor_type and actor_id, which can store the type and ID
            // of the actor responsible for the event
            $table->string('actor_type')->nullable();
            $table->string('actor_id')->nullable();

            // Add a JSON column for additional data related to the event, which can store any relevant information
            $table->json('data')->nullable();

            // Add a timestamp for when the event occurred, indexed for efficient querying
            $table->timestamp('occurred_at')->index();

            // Add timestamps for created_at and updated_at
            $table->timestamps();

            // Add indexes for subject and actor with occurred_at for efficient querying
            $table->index(['subject_type', 'subject_id', 'occurred_at'], 'passage_audit_subject_time_index');
            $table->index(['actor_type', 'actor_id', 'occurred_at'], 'passage_audit_actor_time_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the audits table if it exists, using the table name from the configuration
        Schema::dropIfExists((string) config('passage.tables.audits', 'passage_audits'));
    }
};
