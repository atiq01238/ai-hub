<?php

namespace App\Console\Commands;

use App\Models\Tool;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditToolProfileIntelligence extends Command
{
    protected $signature = 'tools:v3-profile-audit {--published}';
    protected $description = 'Audit Phase 3 tool profile intelligence coverage.';

    public function handle(): int
    {
        $ids = Tool::query()->when($this->option('published'), fn ($q) => $q->where('status','published'))->pluck('id');
        $count = $ids->count();
        $unknownLifecycle = Tool::whereIn('id',$ids)->where(fn ($q) => $q->whereNull('product_status')->orWhere('product_status','unknown'))->count();
        $verifiedLifecycle = Tool::whereIn('id',$ids)->whereNotNull('product_status_verified_at')->count();
        $missingUseCases = Tool::whereIn('id',$ids)->whereDoesntHave('useCaseTerms')->count();
        $featureMappings = DB::table('feature_tool')->whereIn('tool_id',$ids)->count();
        $featureNoSource = DB::table('feature_tool')->whereIn('tool_id',$ids)->whereNull('tool_source_id')->count();
        $featureNoDescription = DB::table('feature_tool as ft')
            ->join('features as f','f.id','=','ft.feature_id')
            ->whereIn('ft.tool_id',$ids)
            ->where(function ($q) {
                $q->where(function ($inner) { $inner->whereNull('ft.description')->orWhere('ft.description',''); })
                    ->where(function ($inner) { $inner->whereNull('f.short_description')->orWhere('f.short_description',''); })
                    ->where(function ($inner) { $inner->whereNull('f.description')->orWhere('f.description',''); });
            })->count();
        $verifiedFeatures = DB::table('feature_tool')->whereIn('tool_id',$ids)->where('verification_status','verified')->count();
        $useCaseMappings = DB::table('tool_use_case')->whereIn('tool_id',$ids)->count();
        $useCaseNoSource = DB::table('tool_use_case')->whereIn('tool_id',$ids)->whereNull('tool_source_id')->count();
        $verifiedUseCases = DB::table('tool_use_case')->whereIn('tool_id',$ids)->where('verification_status','verified')->count();

        $this->table(['Phase 3 check','Count'], [
            ['Tools audited',$count],
            ['Lifecycle status unknown',$unknownLifecycle],
            ['Lifecycle status verified',$verifiedLifecycle],
            ['Missing Best-for use cases',$missingUseCases],
            ['Feature mappings',$featureMappings],
            ['Feature mappings missing source link',$featureNoSource],
            ['Feature mappings missing any effective description',$featureNoDescription],
            ['Verified feature mappings',$verifiedFeatures],
            ['Use-case mappings',$useCaseMappings],
            ['Use-case mappings missing source link',$useCaseNoSource],
            ['Verified use-case mappings',$verifiedUseCases],
        ]);
        return self::SUCCESS;
    }
}
