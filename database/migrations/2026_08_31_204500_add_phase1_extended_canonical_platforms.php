<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('platforms')) {
            return;
        }

        $canonical = [
            ['IDE', 'ide', 'ide', 180],
            ['AWS Console', 'aws-console', 'cloud_console', 190],
            ['On-Premises', 'on-premises', 'deployment', 200],
            ['iPadOS', 'ipados', 'mobile_os', 210],
            ['Adobe Apps', 'adobe-apps', 'ecosystem', 220],
            ['Discord', 'discord', 'access', 230],
            ['Embedded', 'embedded', 'deployment', 240],
            ['Git', 'git', 'developer', 250],
            ['GitHub', 'github', 'developer_platform', 260],
            ['GitLab', 'gitlab', 'developer_platform', 270],
            ['Local', 'local', 'deployment', 280],
            ['Robotics', 'robotics', 'physical_platform', 290],
            ['Vehicle', 'vehicle', 'physical_platform', 300],
        ];

        $now = now();
        foreach ($canonical as [$name, $slug, $group, $sortOrder]) {
            DB::table('platforms')->updateOrInsert(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'group' => $group,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('platforms')) {
            return;
        }

        DB::table('platforms')->whereIn('slug', [
            'ide',
            'aws-console',
            'on-premises',
            'ipados',
            'adobe-apps',
            'discord',
            'embedded',
            'git',
            'github',
            'gitlab',
            'local',
            'robotics',
            'vehicle',
        ])->delete();
    }
};
