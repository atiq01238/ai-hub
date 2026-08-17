<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('roles') || ! Schema::hasTable('users')) {
            return;
        }

        DB::transaction(function () {
            $superAdmin = DB::table('roles')
                ->where('slug', 'super-admin')
                ->orWhereRaw('LOWER(name) = ?', ['super admin'])
                ->first();

            $legacyAdmin = DB::table('roles')
                ->where('slug', 'admin')
                ->orWhereRaw('LOWER(name) = ?', ['admin'])
                ->first();

            // If the project only had a generic granular "admin" role, promote
            // that exact row to the protected Super Admin role so user FKs stay valid.
            if (! $superAdmin && $legacyAdmin) {
                DB::table('roles')->where('id', $legacyAdmin->id)->update([
                    'name' => 'Super Admin',
                    'slug' => 'super-admin',
                    'updated_at' => now(),
                ]);

                $superAdmin = DB::table('roles')->where('id', $legacyAdmin->id)->first();
                $legacyAdmin = null;
            }

            // If neither role exists, create the one required system role.
            if (! $superAdmin) {
                $id = DB::table('roles')->insertGetId([
                    'name' => 'Super Admin',
                    'slug' => 'super-admin',
                    'color' => '#7c3aed',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $superAdmin = DB::table('roles')->where('id', $id)->first();
            }

            // When both legacy Admin and Super Admin exist, move users first,
            // then remove the redundant granular Admin role. The simple
            // users.role = admin value remains the Access Type gate.
            if ($legacyAdmin && (int) $legacyAdmin->id !== (int) $superAdmin->id) {
                DB::table('users')
                    ->where('role_id', $legacyAdmin->id)
                    ->update(['role_id' => $superAdmin->id]);

                if (Schema::hasTable('role_permissions')) {
                    DB::table('role_permissions')->where('role_id', $legacyAdmin->id)->delete();
                }

                DB::table('roles')->where('id', $legacyAdmin->id)->delete();
            }

            // Eliminate role-less administrators. After this migration every
            // Administrator has an explicit Permission Role.
            DB::table('users')
                ->where('role', 'admin')
                ->whereNull('role_id')
                ->update(['role_id' => $superAdmin->id]);

            // Members must never retain an admin permission role.
            DB::table('users')
                ->where('role', '!=', 'admin')
                ->whereNotNull('role_id')
                ->update(['role_id' => null]);
        });
    }

    public function down(): void
    {
        // Intentional no-op: this is a data normalization migration. Recreating
        // a redundant legacy Admin role would make the authorization model ambiguous.
    }
};
