<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('email_enabled')->default(true);
            $table->boolean('breaking_news')->default(true);
            $table->boolean('new_models')->default(true);
            $table->boolean('new_tools')->default(true);
            $table->boolean('followed_entities')->default(true);
            $table->boolean('benchmark_updates')->default(false);
            $table->boolean('price_changes')->default(false);
            $table->boolean('weekly_digest')->default(true);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });

        DB::table('users')->orderBy('id')->chunkById(500, function ($users): void {
            $now = now();
            DB::table('email_preferences')->insert($users->map(fn ($user) => [
                'user_id' => $user->id,
                'email_enabled' => true,
                'breaking_news' => true,
                'new_models' => true,
                'new_tools' => true,
                'followed_entities' => true,
                'benchmark_updates' => false,
                'price_changes' => false,
                'weekly_digest' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all());
        }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('email_preferences');
    }
};
