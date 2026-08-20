<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Trust threshold
    |--------------------------------------------------------------------------
    |
    | A normal member becomes eligible for automatic trust after this many
    | published comments/replies with no spam moderation outcome.
    |
    */
    'approved_before_trusted' => 3,

    /*
    |--------------------------------------------------------------------------
    | Suspicion rules
    |--------------------------------------------------------------------------
    */
    'max_links' => 0,
    'long_comment_threshold' => 1600,
    'uppercase_ratio_threshold' => 0.72,
    'duplicate_window_hours' => 24,

    /*
    |--------------------------------------------------------------------------
    | Lightweight spam phrases
    |--------------------------------------------------------------------------
    |
    | These are intentionally conservative. The goal is to route suspicious
    | content to moderation, not automatically delete it.
    |
    */
    'spam_phrases' => [
        'buy followers',
        'guaranteed profit',
        'double your money',
        'crypto giveaway',
        'contact me on telegram',
        'whatsapp me',
        'click my link',
        'free money',
    ],
];
