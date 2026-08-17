<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->string('submission_type', 32)->default('tool')->after('user_id')->index();
            $table->foreignId('reviewed_by')->nullable()->after('admin_notes')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->foreignId('converted_tool_id')->nullable()->after('reviewed_at')
                ->constrained('tools')->nullOnDelete();
            $table->index(['submission_type', 'status'], 'community_submissions_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('converted_tool_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropIndex('community_submissions_type_status_index');
            $table->dropColumn(['submission_type', 'reviewed_at']);
        });
    }
};
