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

    'test_types' => [
        'objective' => [
            'label' => 'Objective / factual',
            'description' => 'Math, factual and answer-key tasks with a clearly verifiable target.',
            'rubric' => ['correctness' => 70, 'reasoning' => 15, 'instruction_following' => 15],
        ],
        'coding' => [
            'label' => 'Coding',
            'description' => 'Code generation, debugging and implementation tasks.',
            'rubric' => ['correctness' => 40, 'code_quality' => 20, 'instruction_following' => 20, 'explanation' => 10, 'efficiency' => 10],
        ],
        'reasoning' => [
            'label' => 'Reasoning',
            'description' => 'Logic, planning and multi-step problem solving.',
            'rubric' => ['correctness' => 40, 'reasoning' => 35, 'instruction_following' => 15, 'explanation' => 10],
        ],
        'instruction_following' => [
            'label' => 'Instruction following',
            'description' => 'Constraint compliance, requested format and exact task execution.',
            'rubric' => ['instruction_following' => 60, 'quality' => 20, 'structure' => 20],
        ],
        'writing' => [
            'label' => 'Writing',
            'description' => 'Writing quality, structure, style and originality.',
            'rubric' => ['quality' => 30, 'instruction_following' => 25, 'structure' => 20, 'creativity' => 15, 'language' => 10],
        ],
        'research' => [
            'label' => 'Research',
            'description' => 'Synthesis, evidence handling and information organization.',
            'rubric' => ['correctness' => 25, 'research_quality' => 30, 'instruction_following' => 20, 'quality' => 15, 'structure' => 10],
        ],
        'long_context' => [
            'label' => 'Long context',
            'description' => 'Retrieval and synthesis from long or complex context.',
            'rubric' => ['context_retrieval' => 40, 'correctness' => 25, 'instruction_following' => 20, 'quality' => 15],
        ],
        'multimodal' => [
            'label' => 'Multimodal',
            'description' => 'Image, audio, video or mixed-input understanding.',
            'rubric' => ['correctness' => 35, 'multimodal_understanding' => 35, 'instruction_following' => 15, 'quality' => 15],
        ],
        'performance' => [
            'label' => 'Performance',
            'description' => 'Correctness under latency or responsiveness constraints.',
            'rubric' => ['correctness' => 45, 'instruction_following' => 15, 'speed' => 40],
        ],
        'safety_reliability' => [
            'label' => 'Safety & reliability',
            'description' => 'Reliability, uncertainty handling and safety behavior.',
            'rubric' => ['safety_reliability' => 50, 'correctness' => 25, 'instruction_following' => 25],
        ],
    ],

    'rubric_library' => [
        'correctness' => [
            'label' => 'Correctness',
            'description' => 'Technical or factual correctness relative to the task and answer key.',
            'auto_strategy' => 'answer_key',
        ],
        'reasoning' => [
            'label' => 'Reasoning',
            'description' => 'Soundness and completeness of the reasoning needed to reach the answer.',
            'auto_strategy' => 'manual',
        ],
        'instruction_following' => [
            'label' => 'Instruction following',
            'description' => 'Compliance with explicit constraints, requested content and output format.',
            'auto_strategy' => 'prompt_constraints',
        ],
        'code_quality' => [
            'label' => 'Code quality',
            'description' => 'Maintainability, conventions, clarity and implementation quality.',
            'auto_strategy' => 'manual',
        ],
        'explanation' => [
            'label' => 'Explanation',
            'description' => 'Clarity and usefulness of the explanation or justification.',
            'auto_strategy' => 'manual',
        ],
        'efficiency' => [
            'label' => 'Efficiency',
            'description' => 'Avoids unnecessary work while solving the task completely.',
            'auto_strategy' => 'manual',
        ],
        'quality' => [
            'label' => 'Quality',
            'description' => 'Usefulness, completeness, clarity and overall response quality.',
            'auto_strategy' => 'manual',
        ],
        'structure' => [
            'label' => 'Structure',
            'description' => 'Organization and adherence to any explicitly requested output structure.',
            'auto_strategy' => 'structure',
        ],
        'creativity' => [
            'label' => 'Creativity',
            'description' => 'Originality and strength of ideas where creativity is part of the task.',
            'auto_strategy' => 'manual',
        ],
        'language' => [
            'label' => 'Language',
            'description' => 'Grammar, fluency, tone and language control.',
            'auto_strategy' => 'manual',
        ],
        'research_quality' => [
            'label' => 'Research quality',
            'description' => 'Evidence use, synthesis quality, source handling and uncertainty discipline.',
            'auto_strategy' => 'manual',
        ],
        'context_retrieval' => [
            'label' => 'Context retrieval',
            'description' => 'Finds and uses the relevant information from the supplied context.',
            'auto_strategy' => 'answer_key',
        ],
        'multimodal_understanding' => [
            'label' => 'Multimodal understanding',
            'description' => 'Correctly understands and combines the non-text inputs required by the task.',
            'auto_strategy' => 'manual',
        ],
        'safety_reliability' => [
            'label' => 'Safety & reliability',
            'description' => 'Reliable behavior, calibrated uncertainty and appropriate safety handling.',
            'auto_strategy' => 'manual',
        ],
        'speed' => [
            'label' => 'Speed',
            'description' => 'Responsiveness based on recorded latency. N/A when latency is not recorded.',
            'auto_strategy' => 'latency',
        ],
    ],

    // Legacy five-field definitions are retained for old records and compatibility.
    'criteria' => [
        'quality' => ['label' => 'Quality', 'field' => 'score_quality', 'description' => 'Usefulness, clarity, completeness and overall response quality.'],
        'accuracy' => ['label' => 'Accuracy', 'field' => 'score_accuracy', 'description' => 'Factual or technical correctness relative to the task and expected result.'],
        'prompt_adherence' => ['label' => 'Prompt adherence', 'field' => 'score_prompt_adherence', 'description' => 'How closely the model follows instructions, constraints and requested format.'],
        'creativity' => ['label' => 'Creativity', 'field' => 'score_creativity', 'description' => 'Originality and quality of ideas where creativity is relevant to the task.'],
        'speed' => ['label' => 'Speed', 'field' => 'score_speed', 'description' => 'Relative responsiveness for this test, informed by recorded latency where available.'],
    ],

    'default_weights' => [
        'quality' => 25,
        'accuracy' => 30,
        'prompt_adherence' => 25,
        'creativity' => 10,
        'speed' => 10,
    ],

    'run_modes' => [
        'quick' => ['label' => 'Quick test', 'runs' => 1, 'description' => 'One controlled run per model. Best for drafts and fast experiments.'],
        'verified' => ['label' => 'Verified test', 'runs' => 3, 'description' => 'Three controlled runs per model. Final scores use the run average and range.'],
    ],

    'verification_levels' => [
        'unverified' => 'Unverified',
        'reviewed' => 'Reviewed',
        'verified' => 'Verified',
        'high_confidence' => 'High-confidence verified',
    ],

    'result_statuses' => [
        'pending' => 'Pending',
        'complete' => 'Complete',
        'excluded' => 'Excluded',
    ],

    'model_limit' => 6,
];
