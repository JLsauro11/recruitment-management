<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('department_id')
                ->constrained('departments')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->unique(['department_id', 'name']);
        });

        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('position_id')
                ->constrained('positions')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('title');
            $table->unsignedInteger('slots')->default(1);

            $table->enum('employment_type', [
                'Full-time',
                'Part-time',
                'Contract',
                'Internship',
            ])->default('Full-time');

            $table->text('description')->nullable();
            $table->text('qualifications')->nullable();
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->date('opening_date');
            $table->date('closing_date')->nullable();

            $table->enum('status', [
                'Draft',
                'Open',
                'Closed',
                'Cancelled',
            ])->default('Draft');

            $table->timestamps();
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->id();

            /*
             * Required at unique ang user_id:
             * bawat applicant ay may sariling login account.
             */
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('mobile', 20);
            $table->text('address');
            $table->date('birthdate')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Prefer not to say'])->nullable();
            $table->string('resume_path')->nullable();
            $table->timestamps();
        });

        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('applicant_id')
                ->constrained('applicants')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreignId('job_vacancy_id')
                ->constrained('job_vacancies')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->string('reference_no')->unique();

            $table->enum('status', [
                'New Applicant',
                'For Screening',
                'For Initial Interview',
                'For Examination',
                'For Final Interview',
                'For Job Offer',
                'Hired',
                'Rejected',
                'Withdrawn',
            ])->default('New Applicant');

            $table->text('remarks')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamps();

            /*
             * Pinipigilan ang applicant na dalawang beses mag-apply
             * sa parehong vacancy.
             */
            $table->unique(['applicant_id', 'job_vacancy_id']);
        });

        Schema::create('interviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            /*
             * Nullable ito dahil maaaring hindi pa assigned
             * ang interviewer habang ginagawa ang schedule.
             */
            $table->foreignId('interviewer_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->enum('type', [
                'Initial Interview',
                'HR Interview',
                'Technical Interview',
                'Final Interview',
            ]);

            $table->dateTime('scheduled_at');
            $table->string('location')->nullable();
            $table->string('meeting_link')->nullable();

            $table->enum('status', [
                'Scheduled',
                'Completed',
                'Cancelled',
                'Rescheduled',
                'No Show',
            ])->default('Scheduled');

            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('exam_type');
            $table->decimal('score', 8, 2);
            $table->decimal('passing_score', 8, 2);

            $table->enum('result', [
                'Passed',
                'Failed',
            ]);

            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->unique(['application_id', 'exam_type']);
        });

        Schema::create('application_status_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')
                ->constrained('applications')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->string('status');
            $table->text('remarks')->nullable();

            /*
             * Nullable ito dahil maaaring system-generated ang status.
             */
            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_status_histories');
        Schema::dropIfExists('exam_results');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('applications');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('job_vacancies');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};