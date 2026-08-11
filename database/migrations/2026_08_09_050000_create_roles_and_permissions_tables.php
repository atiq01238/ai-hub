<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#5b7fff'); // hex, for the UI accent bar
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained()->cascadeOnDelete();
            $table->string('module');   // e.g. "AI Tools"
            $table->string('action');   // e.g. "View", "Add", "Edit", "Delete", "Publish", "Export"
            $table->boolean('allowed')->default(false);
            $table->timestamps();

            $table->unique(['role_id', 'module', 'action']);
        });

        // This is a SEPARATE, granular role from the simple 'role' string
        // column you already use for the admin/user gate — that one keeps
        // working exactly as before. This new role_id is optional and only
        // used by this permissions matrix feature.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->after('role')->constrained('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('role_id');
        });
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('roles');
    }
};
