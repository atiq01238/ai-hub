<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analytics_visitors', function (Blueprint $table) {
            $table->id();
            $table->char('visitor_key', 64)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('first_seen_at')->index();
            $table->dateTime('last_seen_at')->index();
            $table->string('first_landing_path', 1024)->nullable();
            $table->string('first_referrer_domain', 190)->nullable();
            $table->string('first_utm_source', 100)->nullable();
            $table->string('first_utm_medium', 100)->nullable();
            $table->string('first_utm_campaign', 150)->nullable();
            $table->timestamps();
        });

        Schema::create('analytics_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('analytics_visitors')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->char('session_key', 64)->unique();
            $table->dateTime('started_at')->index();
            $table->dateTime('last_seen_at')->index();
            $table->string('landing_path', 1024)->nullable();
            $table->string('referrer_domain', 190)->nullable()->index();
            $table->string('utm_source', 100)->nullable();
            $table->string('utm_medium', 100)->nullable();
            $table->string('utm_campaign', 150)->nullable();
            $table->string('device_type', 20)->default('desktop')->index();
            $table->string('browser', 50)->nullable()->index();
            $table->string('operating_system', 50)->nullable();
            $table->char('country_code', 2)->nullable()->index();
            $table->unsignedInteger('page_views')->default(0);
            $table->timestamps();

            $table->index(['visitor_id', 'started_at']);
        });

        Schema::create('analytics_page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->constrained('analytics_visitors')->cascadeOnDelete();
            $table->foreignId('analytics_session_id')->constrained('analytics_sessions')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route_name', 140)->nullable()->index();
            $table->string('path', 1024);
            $table->string('entity_type', 40)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->boolean('is_entry')->default(false);
            $table->dateTime('viewed_at')->index();

            $table->index(['visitor_id', 'viewed_at']);
            $table->index(['analytics_session_id', 'viewed_at'], 'analytics_pv_session_time_idx');
            $table->index(['entity_type', 'entity_id', 'viewed_at'], 'analytics_pv_entity_time_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analytics_page_views');
        Schema::dropIfExists('analytics_sessions');
        Schema::dropIfExists('analytics_visitors');
    }
};
