<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // if submitted while logged in

            $table->string('tool_name');
            $table->string('submitted_by_email');
            $table->string('website')->nullable();
            $table->string('category')->nullable();
            $table->text('description')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected', 'needs_info'])->default('pending');
            $table->text('admin_notes')->nullable(); // e.g. what info was requested, or why rejected

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submissions');
    }
};
