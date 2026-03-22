<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('recording_id')->constrained('recordings')->cascadeOnDelete();
            $table->foreignUuid('ai_intent_id')->constrained('ai_intents')->cascadeOnDelete();
            $table->foreignUuid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('engine')->default('n8n');
            $table->string('variant');
            $table->integer('version')->default(1);
            $table->jsonb('workflow_json');
            $table->integer('node_count')->nullable();
            $table->string('status');
            $table->timestamps();

            $table->index(['organization_id', 'engine']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflows');
    }
};
