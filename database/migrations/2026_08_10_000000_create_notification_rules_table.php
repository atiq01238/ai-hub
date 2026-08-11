<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_rules', function (Blueprint $table) {
            $table->id();
            $table->string('trigger_key')->unique(); // matches a key checked in code
            $table->string('label');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        // Seed the 3 triggers that actually exist in the app right now.
        DB::table('notification_rules')->insert([
            ['trigger_key' => 'new_submission', 'label' => 'New tool submission received', 'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['trigger_key' => 'pending_review',  'label' => 'Review awaiting approval',       'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['trigger_key' => 'price_change',    'label' => 'Tool pricing changed',            'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_rules');
    }
};
