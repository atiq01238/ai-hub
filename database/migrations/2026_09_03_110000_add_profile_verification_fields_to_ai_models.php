<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (! Schema::hasColumn('ai_models', 'profile_verification_status')) {
                $table->string('profile_verification_status', 80)->nullable()->after('identity_verified_at');
            }
            if (! Schema::hasColumn('ai_models', 'profile_verified_at')) {
                $table->timestamp('profile_verified_at')->nullable()->after('profile_verification_status')->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ai_models', function (Blueprint $table) {
            if (Schema::hasColumn('ai_models', 'profile_verified_at')) {
                $table->dropIndex(['profile_verified_at']);
                $table->dropColumn('profile_verified_at');
            }
            if (Schema::hasColumn('ai_models', 'profile_verification_status')) {
                $table->dropColumn('profile_verification_status');
            }
        });
    }
};
