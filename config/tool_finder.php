<?php

return [
    'shortcuts' => [
        'coding' => [
            'label' => 'Coding',
            'icon' => 'code-2',
            'category' => 'coding-development',
        ],
        'writing' => [
            'label' => 'Writing',
            'icon' => 'pen-line',
            'category' => 'writing-content',
        ],
        'image' => [
            'label' => 'Image',
            'icon' => 'image',
            'category' => 'image-design',
        ],
        'video' => [
            'label' => 'Video',
            'icon' => 'clapperboard',
            'category' => 'video-animation',
        ],
        'research' => [
            'label' => 'Research',
            'icon' => 'search',
            'category' => 'search-research',
        ],
        'audio' => [
            'label' => 'Audio',
            'icon' => 'mic-2',
            'category' => 'voice-audio',
        ],
        'marketing' => [
            'label' => 'Marketing',
            'icon' => 'megaphone',
            'category' => 'marketing-sales',
        ],
        'productivity' => [
            'label' => 'Productivity',
            'icon' => 'zap',
            'category' => 'productivity-office',
        ],
    ],

    // These signals intentionally map everyday language to the canonical
    // product taxonomy already used by AI Orbit. They do not invent facts
    // about a tool; they only help interpret the visitor's request.
    'category_signals' => [
        'coding-development' => ['code', 'coding', 'programming', 'developer', 'development', 'debug', 'debugging', 'laravel', 'php', 'javascript', 'typescript', 'python', 'ide', 'website', 'webapp', 'app', 'software'],
        'writing-content' => ['write', 'writing', 'writer', 'content', 'copy', 'copywriting', 'blog', 'article', 'caption', 'script', 'proofread', 'proofreading', 'grammar', 'rewrite'],
        'image-design' => ['image', 'photo', 'picture', 'design', 'graphic', 'logo', 'brand', 'illustration', 'thumbnail', 'background', 'product-photo'],
        'video-animation' => ['video', 'youtube', 'animation', 'animate', 'avatar', 'clip', 'reel', 'shorts', 'filmmaking', 'film'],
        'voice-audio' => ['audio', 'voice', 'voiceover', 'voice-over', 'speech', 'transcription', 'transcribe', 'dubbing', 'dub', 'podcast'],
        'music' => ['music', 'song', 'songwriting', 'instrumental', 'mastering', 'vocal', 'beat'],
        'search-research' => ['research', 'search', 'citation', 'citations', 'source', 'sources', 'paper', 'papers', 'academic', 'pdf', 'document', 'documents', 'evidence', 'literature'],
        'agents-automation' => ['agent', 'agents', 'automation', 'automate', 'workflow', 'workflows', 'browser-agent', 'computer-use', 'autonomous', 'zapier'],
        'productivity-office' => ['productivity', 'office', 'meeting', 'meetings', 'notes', 'note', 'presentation', 'slides', 'email', 'calendar', 'document-work'],
        'data-analytics' => ['data', 'analytics', 'analysis', 'spreadsheet', 'excel', 'sql', 'database', 'dashboard', 'visualization', 'forecast'],
        'marketing-sales' => ['marketing', 'sales', 'seo', 'advertising', 'advertisement', 'ads', 'campaign', 'lead', 'leads', 'social-media', 'growth'],
        'customer-support' => ['support', 'customer', 'helpdesk', 'help-desk', 'ticket', 'tickets', 'contact-center', 'customer-service'],
        'education-learning' => ['education', 'student', 'students', 'study', 'learning', 'learn', 'teacher', 'teachers', 'tutor', 'homework', 'course'],
        'chat-assistants' => ['assistant', 'chat', 'chatbot', 'general-ai', 'question-answering', 'reasoning-assistant'],
    ],

    'feature_signals' => [
        'Document & PDF Analysis' => ['pdf', 'pdfs', 'document', 'documents', 'summarize', 'summary', 'summarization'],
        'Code Generation' => ['code', 'coding', 'programming', 'function', 'component', 'laravel', 'php'],
        'Code Review & Debugging' => ['debug', 'debugging', 'bug', 'bugs', 'review-code', 'fix-code'],
        'Image Generation' => ['generate-image', 'image-generation', 'create-image', 'art', 'illustration', 'thumbnail'],
        'Image Editing' => ['edit-image', 'photo-editing', 'background-remove', 'remove-background', 'retouch'],
        'Video Generation' => ['generate-video', 'text-to-video', 'video-generation', 'create-video'],
        'Video Editing' => ['edit-video', 'video-editing', 'cut-video'],
        'Text to Speech' => ['text-to-speech', 'voiceover', 'voice-over', 'tts', 'narration'],
        'Speech to Text' => ['speech-to-text', 'transcription', 'transcribe', 'meeting-transcription', 'stt'],
        'Voice Cloning' => ['voice-clone', 'voice-cloning', 'clone-voice'],
        'Web Browsing & Search' => ['web-search', 'search-web', 'browse-web', 'current-information'],
        'Deep Research' => ['deep-research', 'research', 'citations', 'sources', 'evidence'],
        'API Access' => ['api', 'developer-api', 'integration-api'],
        'Agent Workflows' => ['agent', 'agents', 'workflow', 'automation', 'autonomous'],
        'Browser Automation' => ['browser-automation', 'browser-agent', 'web-automation'],
        'Computer Use' => ['computer-use', 'desktop-agent'],
        'Music Generation' => ['music-generation', 'generate-music', 'song-generation', 'generate-song'],
        'Reasoning' => ['reasoning', 'problem-solving', 'analysis'],
        'Text Generation' => ['text-generation', 'writing', 'write', 'content-generation', 'copywriting'],
    ],


    // Phase 2: map natural-language jobs to the canonical use-case taxonomy.
    // These are intent hints only; a tool receives the boost only when the
    // corresponding use case actually exists on its profile.
    'use_case_signals' => [
        'Writing & Editing' => ['write', 'writing', 'rewrite', 'proofread', 'grammar', 'copywriting', 'blog post', 'article writing'],
        'Summarization' => ['summarize', 'summary', 'summarization', 'shorten', 'key points'],
        'Document Analysis' => ['pdf', 'document', 'documents', 'analyze document', 'read pdf', 'ask pdf'],
        'Web Research' => ['web research', 'search web', 'find sources', 'current information', 'online research'],
        'Deep Research' => ['deep research', 'citations', 'evidence', 'research report', 'source backed'],
        'Academic Research' => ['academic', 'paper', 'papers', 'literature review', 'study research'],
        'Code Completion' => ['coding', 'write code', 'code completion', 'autocomplete', 'laravel', 'php', 'javascript', 'python'],
        'Debugging' => ['debug', 'debugging', 'fix bug', 'fix error', 'error in code'],
        'Code Review' => ['code review', 'review code', 'review pull request', 'review pr'],
        'App Prototyping' => ['build app', 'prototype app', 'app builder', 'website builder', 'web app'],
        'Image Creation' => ['generate image', 'create image', 'image generation', 'illustration', 'thumbnail'],
        'Image Editing' => ['edit image', 'photo editing', 'remove background', 'retouch'],
        'Video Creation' => ['generate video', 'create video', 'text to video', 'youtube video', 'video generation'],
        'Video Editing' => ['edit video', 'video editing', 'cut video'],
        'Social Video Production' => ['reels', 'shorts', 'tiktok', 'social video'],
        'Voiceovers' => ['voiceover', 'voice over', 'narration', 'text to speech'],
        'Transcription' => ['transcribe', 'transcription', 'speech to text', 'meeting transcript'],
        'Dubbing & Localization' => ['dubbing', 'dub video', 'localize voice', 'translate voice'],
        'Music Creation' => ['generate music', 'music generation', 'make song', 'song generation', 'beat'],
        'Workflow Automation' => ['workflow automation', 'automate workflow', 'automation'],
        'Task Automation' => ['task automation', 'automate task', 'agent task'],
        'Browser Automation' => ['browser automation', 'browser agent', 'automate browser'],
        'SEO Content' => ['seo content', 'seo writing', 'keyword content', 'search optimized content'],
        'Social Media Content' => ['social media', 'instagram caption', 'linkedin post', 'social post'],
        'Email Writing' => ['email writing', 'write email', 'sales email', 'cold email'],
        'Presentation Creation' => ['presentation', 'slides', 'powerpoint', 'pitch deck'],
        'Data Analysis' => ['data analysis', 'analyze data', 'analytics', 'dashboard'],
        'Spreadsheet Analysis' => ['spreadsheet', 'excel', 'google sheets', 'csv analysis'],
        'SQL Assistance' => ['sql', 'database query', 'write query'],
        'Study & Learning' => ['study', 'student', 'learning', 'homework', 'exam prep'],
        'Customer Support' => ['customer support', 'helpdesk', 'support chatbot', 'customer service'],
        'Sales Outreach' => ['sales outreach', 'cold outreach', 'prospecting'],
        'Lead Generation' => ['lead generation', 'find leads', 'prospects'],
    ],

    // Soft preferences inferred from the task box. Explicit UI filters always
    // remain authoritative; these signals only improve ordering and explanations.
    'preference_signals' => [
        'free' => ['free', 'free tool', 'no cost', 'without paying'],
        'api' => ['api', 'developer api', 'programmatic access'],
        'open_source' => ['open source', 'self hosted', 'self host', 'local ai'],
        'privacy' => ['privacy', 'private', 'do not train', 'no training', 'local data'],
        'beginner' => ['beginner', 'easy to use', 'simple', 'no code', 'no-code'],
        'team' => ['team', 'collaboration', 'workspace', 'multiple users'],
        'mobile' => ['mobile app', 'iphone', 'android', 'ios'],
    ],

    'matching' => [
        'minimum_task_score' => 24,
        'max_match_percent' => 98,
        'evidence_tiebreak_max' => 40,
    ],

    'token_aliases' => [
        'pdfs' => 'pdf',
        'photos' => 'photo',
        'pictures' => 'image',
        'images' => 'image',
        'videos' => 'video',
        'voiceovers' => 'voiceover',
        'presentations' => 'presentation',
        'meetings' => 'meeting',
        'citations' => 'citation',
        'codes' => 'code',
        'programmer' => 'developer',
        'programmers' => 'developer',
        'developers' => 'developer',
        'articles' => 'article',
        'blogs' => 'blog',
        'summaries' => 'summary',
        'summarizing' => 'summarize',
        'transcribing' => 'transcribe',
        'automating' => 'automate',
        'workflows' => 'workflow',
        'students' => 'student',
        'teachers' => 'teacher',
    ],
];
