<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Search index quality gates
    |--------------------------------------------------------------------------
    |
    | These thresholds only control search-engine indexability. A public record
    | can remain browsable on AI Orbit while receiving noindex,follow until the
    | underlying profile/editorial content is strong enough to enter sitemaps.
    |
    */
    'tool' => [
        // Phase 3.1: keep every published tool indexable while the live 318-tool
        // catalog is audited/enriched. Switch to false only after the catalog
        // cleanup is complete and strict quality gating is intentionally restored.
        'index_published_by_default' => true,
        'index_threshold' => 55,
        'min_description_chars' => 100,
        'min_profile_completeness' => 30,
    ],

    'model' => [
        'index_threshold' => 60,
        'min_confidence' => 55,
        'min_notes_chars' => 80,
        'min_capability_signals' => 2,
    ],

    'article' => [
        // Phase 5: 300 words is a floor, not a target. Editorial structure,
        // reviewer/author signals, practical sections and entity context also
        // contribute to indexability. The preferred target remains higher.
        'index_threshold' => 65,
        'min_words' => 300,
        'preferred_words' => 700,
        'min_headings' => 3,
        'min_summary_chars' => 60,
    ],

    'news' => [
        'index_threshold' => 60,
        'min_summary_chars' => 120,
        'min_why_it_matters_chars' => 60,
    ],
];
