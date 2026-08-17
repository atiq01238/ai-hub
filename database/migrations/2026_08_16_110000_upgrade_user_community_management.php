<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('suspension_reason')->nullable()->after('status');
            $table->timestamp('suspended_at')->nullable()->after('suspension_reason');
            $table->timestamp('suspended_until')->nullable()->after('suspended_at');
            $table->foreignId('suspended_by')->nullable()->after('suspended_until')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('last_login_at')->nullable()->after('remember_token')->index();
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->index(['status', 'role'], 'users_status_role_index');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('suspended_by');
            $table->dropIndex('users_status_role_index');
            $table->dropColumn([
                'suspension_reason',
                'suspended_at',
                'suspended_until',
                'last_login_at',
                'last_login_ip',
            ]);
        });
    }
};
