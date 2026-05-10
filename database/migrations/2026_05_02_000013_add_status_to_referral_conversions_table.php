<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('referral_conversions', function (Blueprint $table): void {
            $table->string('status', 32)->default('pending')->after('commission');
        });
    }

    public function down(): void
    {
        Schema::table('referral_conversions', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};