<?php

return [
    'max_query_length' => 180,
    'candidate_limit' => 240,
    'all_results_limit' => 6,
    'single_type_limit' => 30,
    'suggestion_limit' => 10,

    // Removed only when the query has other meaningful words.
    'stop_words' => [
        'a', 'an', 'and', 'are', 'best', 'for', 'find', 'get', 'give', 'in', 'is',
        'me', 'of', 'on', 'or', 'the', 'to', 'top', 'with', 'ai', 'tool', 'tools',
        'model', 'models', 'software', 'app', 'apps',
    ],

    // Query-understanding groups. Matching one term expands the search to the
    // related vocabulary without changing what the user typed or what is logged.
    'synonym_groups' => [
        ['coding', 'code', 'programming', 'developer', 'development', 'debugging', 'code review'],
        ['image', 'images', 'photo', 'photos', 'picture', 'text to image', 'image generation'],
        ['video', 'videos', 'text to video', 'video generation', 'animation'],
        ['writing', 'writer', 'copywriting', 'content writing', 'text generation'],
        ['research', 'deep research', 'web research', 'search assistant'],
        ['automation', 'workflow', 'workflows', 'agent', 'agents', 'agentic'],
        ['voice', 'speech', 'text to speech', 'tts', 'audio'],
        ['transcription', 'speech to text', 'stt', 'meeting notes'],
        ['presentation', 'presentations', 'slides', 'slide deck', 'powerpoint'],
        ['chatbot', 'chat bot', 'assistant', 'conversational'],
        ['design', 'graphic design', 'ui design', 'ux design'],
        ['marketing', 'seo', 'social media', 'advertising'],
        ['free', 'free plan', 'freemium'],
    ],
];
