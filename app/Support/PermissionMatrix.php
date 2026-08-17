<?php

namespace App\Support;

final class PermissionMatrix
{
    public const ACTIONS = ['View', 'Add', 'Edit', 'Delete', 'Publish', 'Export'];

    /**
     * Central permission catalogue for the admin panel.
     * Only actions listed for a module are meaningful in the UI.
     */
    public static function modules(): array
    {
        return [
            'Dashboard' => ['View'],
            'AI News' => ['View', 'Add', 'Edit', 'Delete', 'Publish', 'Export'],
            'AI Tools' => ['View', 'Add', 'Edit', 'Delete', 'Publish', 'Export'],
            'AI Models' => ['View', 'Add', 'Edit', 'Delete', 'Publish', 'Export'],
            'AI Companies' => ['View', 'Add', 'Edit', 'Delete', 'Export'],
            'Taxonomy' => ['View', 'Add', 'Edit', 'Delete'],
            'Comparisons' => ['View', 'Add', 'Edit', 'Delete', 'Publish', 'Export'],
            'AI Test Lab' => ['View', 'Add', 'Edit', 'Delete', 'Export'],
            'Benchmarks' => ['View', 'Add', 'Edit', 'Delete', 'Export'],
            'Pricing' => ['View', 'Add', 'Edit', 'Delete', 'Export'],
            'Content' => ['View', 'Add', 'Edit', 'Delete', 'Publish', 'Export'],
            'Reviews' => ['View', 'Add', 'Edit', 'Delete', 'Publish', 'Export'],
            'Submissions' => ['View', 'Edit', 'Delete', 'Export'],
            'Reports' => ['View', 'Edit', 'Delete', 'Export'],
            'Users' => ['View', 'Add', 'Edit', 'Delete', 'Export'],
            'Analytics' => ['View', 'Export'],
            'System Health' => ['View'],
            'Error Monitoring' => ['View', 'Edit', 'Delete', 'Export'],
            'API Monitoring' => ['View', 'Edit', 'Export'],
            'Automation' => ['View', 'Edit'],
            'Data Verification' => ['View', 'Edit'],
            'Source Reliability' => ['View', 'Export'],
            'News Sources' => ['View', 'Add', 'Edit', 'Delete'],
            'Security' => ['View', 'Edit', 'Export'],
            'Roles & Permissions' => ['View', 'Add', 'Edit', 'Delete'],
            'Backups' => ['View', 'Add', 'Delete', 'Export'],
            'Notifications' => ['View', 'Edit'],
            'Feature Flags' => ['View', 'Add', 'Edit', 'Delete'],
            'Integrations' => ['View', 'Edit'],
            'SEO' => ['View', 'Edit'],
            'Settings' => ['View', 'Edit'],
        ];
    }

    public static function actions(): array
    {
        return self::ACTIONS;
    }

    public static function supports(string $module, string $action): bool
    {
        return in_array($action, self::modules()[$module] ?? [], true);
    }
}
