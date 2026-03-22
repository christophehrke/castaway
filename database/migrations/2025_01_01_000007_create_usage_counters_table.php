<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('period');
            $table->integer('recordings_count')->default(0);
            $table->integer('conversions_count')->default(0);
            $table->bigInteger('storage_bytes')->default(0);
            $table->bigInteger('ai_tokens_used')->default(0);
            $table->timestamps();

            $table->unique(['organization_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
