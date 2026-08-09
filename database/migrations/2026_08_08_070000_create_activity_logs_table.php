<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('action');        // 'created', 'updated', 'deleted'
            $table->string('subject_type');  // e.g. "App\Models\Tool"
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description')->nullable(); // e.g. `Updated Tool "ChatGPT"`

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
