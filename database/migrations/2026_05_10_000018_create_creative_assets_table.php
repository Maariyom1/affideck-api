<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('creative_assets', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->string('description', 1000)->nullable();
            $table->string('file_path');
            $table->string('file_url');
            $table->string('preview_url');
            $table->string('type', 20)->index();
            $table->string('mime_type', 100);
            $table->unsignedInteger('file_size_kb');
            $table->json('tags')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->string('dimensions', 32)->nullable();
            $table->unsignedInteger('download_count')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('creative_assets');
    }
};
