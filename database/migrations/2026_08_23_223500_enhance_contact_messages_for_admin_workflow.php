<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->text('admin_notes')->nullable()->after('status');
            $table->foreignId('handled_by')->nullable()->after('admin_notes')->constrained('users')->nullOnDelete();
            $table->timestamp('read_at')->nullable()->after('handled_by');
            $table->timestamp('replied_at')->nullable()->after('read_at');
            $table->timestamp('closed_at')->nullable()->after('replied_at');
            $table->timestamp('spam_at')->nullable()->after('closed_at');
            $table->index(['handled_by', 'status'], 'contact_messages_handler_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex('contact_messages_handler_status_index');
            $table->dropConstrainedForeignId('handled_by');
            $table->dropColumn(['admin_notes', 'read_at', 'replied_at', 'closed_at', 'spam_at']);
        });
    }
};
