<?php

return [
    /*
     * Public AI News must pass this score unless an editor explicitly includes it.
     * Scores in the review band stay private until they are improved or overridden.
     */
    'accept_threshold' => 60,
    'review_threshold' => 45,
    'evaluator_version' => 'local-v2',

    // Source-name/domain hints. These are only bonuses; they do not make a broad
    // technology feed AI-relevant by themselves.
    'ai_focused_source_hints' => [
        'artificial intelligence', ' ai ', 'ai news', 'ai blog', 'deepmind',
        'openai', 'anthropic', 'hugging face', 'mistral ai', 'xai',
    ],
];
