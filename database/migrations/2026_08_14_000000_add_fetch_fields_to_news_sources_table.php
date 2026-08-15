<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            // Optional defaults applied to every news item pulled from this
            // source (matches what config/news_sources.php used to hardcode).
            $table->foreignId('company_id')->nullable()->after('url')->constrained()->nullOnDelete();
            $table->string('default_category')->nullable()->after('company_id');

            // Real telemetry — filled in by the fetch command each run, so
            // the "Last Fetched" / "Articles Collected" columns are honest.
            $table->timestamp('last_fetched_at')->nullable()->after('status');
            $table->unsignedInteger('articles_collected')->default(0)->after('last_fetched_at');
            $table->string('last_error')->nullable()->after('articles_collected');
        });
    }

    public function down(): void
    {
        Schema::table('news_sources', function (Blueprint $table) {
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn(['default_category', 'last_fetched_at', 'articles_collected', 'last_error']);
        });
    }
};
