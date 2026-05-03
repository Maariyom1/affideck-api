<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 32);
            $table->decimal('payout', 10, 2);
            $table->json('tags')->nullable();
            $table->json('categories')->nullable();
            $table->json('geo')->nullable();
            $table->string('status', 32)->default('draft');
            $table->integer('clicks')->default(0);
            $table->integer('conversions')->default(0);
            $table->decimal('earnings', 12, 2)->default(0);
            $table->timestamps();
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
