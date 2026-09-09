<?php

namespace App\Console\Commands;

use App\Services\AdSense\AdSenseReadinessService;
use Illuminate\Console\Command;

class AuditAdSenseReadiness extends Command
{
    protected $signature = 'adsense:audit-readiness
        {--details : Show every local readiness check}
        {--url= : Optionally smoke-test a running/local/live base URL, e.g. https://ai-orbit.online}
        {--production : Treat HTTPS/canonical/debug production requirements as blockers}';

    protected $description = 'Run AI Orbit Phase 9 final AdSense pre-review readiness checks without modifying data.';

    public function handle(AdSenseReadinessService $audit): int
    {
        $this->info('AI Orbit Phase 9 — final AdSense pre-review audit');
        $this->line('This is an internal readiness checklist, not a prediction or guarantee of Google approval.');
        $this->newLine();

        $local = $audit->audit((bool) $this->option('production'));
        $this->renderSummary('Application + content readiness', $local);

        if ($this->option('details') || ! $local['ready']) {
            $this->table(
                ['Area', 'Check', 'Status', 'Detail'],
                $local['checks']->map(fn (array $row) => [
                    $row['area'], $row['check'], $row['status'], str($row['detail'])->limit(105),
                ])->all()
            );
        }

        $smoke = null;
        if ($url = trim((string) $this->option('url'))) {
            $this->newLine();
            $this->comment('Public HTTP smoke test: '.$url);
            $smoke = $audit->smokeTest($url);
            $this->renderSummary('HTTP smoke test', $smoke);
            $this->table(
                ['Area', 'Path/check', 'Status', 'Detail'],
                $smoke['checks']->map(fn (array $row) => [
                    $row['area'], $row['check'], $row['status'], str($row['detail'])->limit(105),
                ])->all()
            );
        }

        $ready = $local['ready'] && ($smoke === null || $smoke['ready']);
        $this->newLine();
        if ($ready) {
            $this->info('PASS: no Phase 9 blockers detected. Do the final live/mobile visual check, then the site is ready to be considered for an AdSense re-review.');
        } else {
            $this->error('NOT READY: resolve the BLOCKER rows before requesting another AdSense review.');
        }

        $this->line('No database records were created, changed, published, unpublished or deleted.');

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    private function renderSummary(string $label, array $result): void
    {
        $this->table(['Audit', 'PASS', 'WARN', 'BLOCKER', 'Gate'], [[
            $label,
            $result['passes'],
            $result['warnings'],
            $result['blockers'],
            $result['ready'] ? 'PASS' : 'NEEDS WORK',
        ]]);
    }
}
