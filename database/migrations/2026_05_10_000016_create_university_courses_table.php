<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('university_courses', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->string('description', 1000);
            $table->string('category', 100)->index();
            $table->unsignedInteger('duration_minutes');
            $table->string('instructor');
            $table->string('thumbnail_url')->nullable();
            $table->boolean('is_featured')->default(false)->index();
            $table->longText('content');
            $table->string('video_url')->nullable();
            $table->json('prerequisites')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('university_courses');
    }
};
