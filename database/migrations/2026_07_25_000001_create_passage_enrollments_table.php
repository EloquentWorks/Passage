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
        // Get the table name from the configuration, defaulting to 'passage_enrollments'
        $tableName = (string) config('passage.tables.enrollments', 'passage_enrollments');

        // Check if the table already exists to avoid errors
        if (Schema::hasTable($tableName)) {
            return;
        }

        // Create the table with the specified schema
        Schema::create($tableName, function (Blueprint $table): void {
            // Add an auto-incrementing primary key
            $table->id();
            
            // Add a UUID column for unique identification of each enrollment
            $table->uuid('uuid')->unique();

            // Add polymorphic relationship columns for subject_type and subject_id
            $table->string('subject_type');
            $table->string('subject_id');

            // Add a passage_key column to store the unique key of the passage
            $table->string('passage_key');

            // Add a passage_version column to track the version of the passage
            $table->unsignedInteger('passage_version')->default(1);

            // Add a cycle column to track the number of times a subject has enrolled in the same passage
            $table->unsignedInteger('cycle')->default(1);

            // Add an index for subject_type and subject_id for faster lookups
            $table->string('state')->index();

            // Add a JSON column for metadata, which can store additional information about the enrollment
            $table->json('metadata')->nullable();

            // Add timestamps for various states of the enrollment
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('due_at')->nullable()->index();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();

            // Add a foreign key constraint for subject_type and subject_id referencing the users table
            $table->timestamps();

            // Add a composite unique constraint for subject_type, subject_id, passage_key, and cycle
            $table->unique(
                ['subject_type', 'subject_id', 'passage_key', 'cycle'],
                'passage_subject_key_cycle_unique'
            );

            // Add a composite index for subject_type, subject_id, passage_key, and state
            $table->index(
                ['subject_type', 'subject_id', 'passage_key', 'state'],
                'passage_subject_key_state_index'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the table if it exists, using the configured table name
        Schema::dropIfExists((string) config('passage.tables.enrollments', 'passage_enrollments'));
    }
};
