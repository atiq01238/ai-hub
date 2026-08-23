<?php

return [
    'categories' => [
        'Reasoning' => ['icon' => 'brain-circuit', 'description' => 'Logic, planning, deduction and multi-step problem solving.'],
        'Coding' => ['icon' => 'code-2', 'description' => 'Code generation, debugging, refactoring and software-engineering tasks.'],
        'Writing' => ['icon' => 'pen-line', 'description' => 'Writing quality, structure, tone control and instruction following.'],
        'Research' => ['icon' => 'search-check', 'description' => 'Research synthesis, evidence handling and information organization.'],
        'Math' => ['icon' => 'sigma', 'description' => 'Arithmetic, algebra, quantitative reasoning and structured calculation.'],
        'Long Context' => ['icon' => 'scroll-text', 'description' => 'Retrieval and synthesis across long documents or large prompts.'],
        'Multimodal' => ['icon' => 'scan-eye', 'description' => 'Tasks combining text with visual, audio or other input modalities.'],
        'Image' => ['icon' => 'image', 'description' => 'Image understanding, editing or generation-oriented evaluation.'],
        'Audio' => ['icon' => 'audio-lines', 'description' => 'Speech, transcription, synthesis and audio-understanding evaluation.'],
        'Video' => ['icon' => 'clapperboard', 'description' => 'Video understanding or generation-oriented evaluation.'],
        'Instruction Following' => ['icon' => 'list-checks', 'description' => 'Constraint following, formatting accuracy and task compliance.'],
        'Safety & Reliability' => ['icon' => 'shield-check', 'description' => 'Consistency, refusal behavior, uncertainty handling and reliability.'],
    ],

    'difficulties' => [
        'basic' => 'Basic',
        'standard' => 'Standard',
        'advanced' => 'Advanced',
        'stress' => 'Stress Test',
    ],

    'criteria' => [
        'quality' => [
            'label' => 'Quality',
            'field' => 'score_quality',
            'description' => 'Usefulness, clarity, completeness and overall response quality.',
        ],
        'accuracy' => [
            'label' => 'Accuracy',
            'field' => 'score_accuracy',
            'description' => 'Factual or technical correctness relative to the task and expected result.',
        ],
        'prompt_adherence' => [
            'label' => 'Prompt adherence',
            'field' => 'score_prompt_adherence',
            'description' => 'How closely the model follows instructions, constraints and requested format.',
        ],
        'creativity' => [
            'label' => 'Creativity',
            'field' => 'score_creativity',
            'description' => 'Originality and quality of ideas where creativity is relevant to the task.',
        ],
        'speed' => [
            'label' => 'Speed',
            'field' => 'score_speed',
            'description' => 'Relative responsiveness for this test, informed by recorded latency where available.',
        ],
    ],

    'default_weights' => [
        'quality' => 25,
        'accuracy' => 30,
        'prompt_adherence' => 25,
        'creativity' => 10,
        'speed' => 10,
    ],

    'result_statuses' => [
        'pending' => 'Pending',
        'complete' => 'Complete',
        'excluded' => 'Excluded',
    ],

    'model_limit' => 6,
];
