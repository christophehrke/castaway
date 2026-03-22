<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_intents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recording_id')->constrained('recordings')->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->string('status');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->jsonb('steps')->nullable();
            $table->text('raw_transcript')->nullable();
            $table->jsonb('raw_vision_data')->nullable();
            $table->string('model_used')->nullable();
            $table->integer('tokens_used')->nullable();
            $table->timestamp('processing_started_at')->nullable();
            $table->timestamp('processing_completed_at')->nullable();
            $table->timestamps();

            $table->unique(['recording_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_intents');
    }
};
