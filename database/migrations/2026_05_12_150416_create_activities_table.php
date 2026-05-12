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
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type'); // offer_updated, conversion_earned, asset_uploaded, enrollment_created, etc.
            $table->string('title'); // Human-readable title
            $table->string('value')->nullable(); // Metric/amount (e.g., "$50.00")
            $table->string('icon')->nullable(); // Icon name for frontend
            $table->string('link')->nullable(); // URL to related resource
            $table->string('event_type')->nullable(); // Descriptive event type
            $table->json('metadata')->nullable(); // Extra data (offer_id, conversion_id, etc.)
            $table->timestamps();
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
