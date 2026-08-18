<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('saved_items')) {
            return;
        }

        Schema::create('saved_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('saveable_type', 120);
            $table->unsignedBigInteger('saveable_id');
            $table->timestamps();

            $table->unique(['user_id', 'saveable_type', 'saveable_id'], 'saved_items_user_object_unique');
            $table->index(['saveable_type', 'saveable_id'], 'saved_items_object_index');
            $table->index(['user_id', 'created_at'], 'saved_items_user_recent_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_items');
    }
};
