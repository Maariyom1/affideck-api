<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_enrollments', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('university_course_id');
            $table->foreign('university_course_id')->references('id')->on('university_courses')->cascadeOnDelete();
            $table->unsignedTinyInteger('progress_percent')->default(0);
            $table->unsignedInteger('lessons_completed')->default(0);
            $table->unsignedInteger('lessons_total')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable()->index();
            $table->timestamp('completed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['user_id', 'university_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_enrollments');
    }
};
