<?php

return [
    'categories' => [
        'chat-assistants' => [
            'intro' => 'Chat assistants differ less by headline features than by reasoning quality, memory, multimodal support, integrations and how safely they fit into everyday work. Use this category to compare the practical workflow around the assistant, not just the model name behind it.',
            'factors' => [
                ['title' => 'Conversation quality', 'detail' => 'Compare reasoning, instruction-following, memory and how consistently the assistant handles longer conversations.'],
                ['title' => 'Multimodal support', 'detail' => 'Check whether image, file, voice or web inputs are actually available in the plan and platform you intend to use.'],
                ['title' => 'Workflow fit', 'detail' => 'Look for integrations, project features, collaboration and privacy controls that match personal or team use.'],
            ],
        ],
        'coding-development' => [
            'intro' => 'Coding AI tools range from autocomplete to repository-aware agents. The right choice depends on the size of your codebase, the level of autonomy you want, IDE or terminal preference, and whether source control, tests and review are part of the workflow.',
            'factors' => [
                ['title' => 'Codebase awareness', 'detail' => 'Check whether the tool can reason across multiple files, repositories and project context instead of only completing the current line.'],
                ['title' => 'Agentic actions', 'detail' => 'Compare editing, terminal use, test execution and approval controls before allowing an agent to modify a real project.'],
                ['title' => 'Developer workflow', 'detail' => 'IDE support, CLI access, review flow, pricing and privacy can matter more than raw generation quality for daily use.'],
            ],
        ],
        'image-design' => [
            'intro' => 'Image AI products serve very different jobs: generation, editing, design systems, brand assets and production workflows. Compare the level of control and repeatability you need before focusing on visual style alone.',
            'factors' => [
                ['title' => 'Creative control', 'detail' => 'Look for editing, inpainting, reference images, layout control and consistency features that support iterative work.'],
                ['title' => 'Output rights', 'detail' => 'Review plan terms, commercial-use rules, export quality and any restrictions that affect client or production work.'],
                ['title' => 'Workflow speed', 'detail' => 'Generation limits, batch tools, collaboration and integrations can determine whether a product fits a real design pipeline.'],
            ],
        ],
        'video-animation' => [
            'intro' => 'Video AI tools cover generation, avatar creation, editing, dubbing and animation. Evaluate them by the type of footage you need, the amount of manual control available and the cost of producing usable minutes at scale.',
            'factors' => [
                ['title' => 'Production type', 'detail' => 'Separate text-to-video, avatar, editing, dubbing and animation tools because they solve different parts of the workflow.'],
                ['title' => 'Control & consistency', 'detail' => 'Check shot length, reference controls, character consistency, editing tools and export options rather than demo quality alone.'],
                ['title' => 'Cost per workflow', 'detail' => 'Compare credits, render limits, watermarks and commercial-use terms against the volume of video you actually plan to create.'],
            ],
        ],
        'writing-content' => [
            'intro' => 'Writing AI tools range from grammar and rewriting assistants to research-backed drafting and publishing workflows. Choose based on the kind of text you produce, the amount of source grounding required and how much editorial control you need.',
            'factors' => [
                ['title' => 'Writing task', 'detail' => 'Editing, long-form drafting, SEO content, academic writing and team publishing each require different strengths.'],
                ['title' => 'Source grounding', 'detail' => 'For research-heavy work, check citations, document handling and whether claims can be traced back to sources.'],
                ['title' => 'Editorial workflow', 'detail' => 'Tone controls, collaboration, revision history and export options can be as important as first-draft quality.'],
            ],
        ],
        'voice-audio' => [
            'intro' => 'Voice and audio AI spans speech generation, transcription, dubbing, cleanup and production. Compare language coverage, voice control, latency and usage rights according to whether you are creating media, running calls or processing recordings.',
            'factors' => [
                ['title' => 'Audio task', 'detail' => 'Text-to-speech, transcription, dubbing and audio cleanup are separate workflows and should not be judged by the same feature list.'],
                ['title' => 'Language & voice control', 'detail' => 'Check supported languages, pronunciation controls, cloning rules and consistency for the voices you need.'],
                ['title' => 'Production terms', 'detail' => 'Review export quality, usage limits, API access and commercial rights before deploying audio at scale.'],
            ],
        ],
        'music' => [
            'intro' => 'Music AI tools cover song generation, background music, vocals, remixing, composition and mastering. The best fit depends on whether you need ideas, finished tracks or production assistance, and on the rights attached to the output.',
            'factors' => [
                ['title' => 'What you are creating', 'detail' => 'Separate full-song generators from composition, mastering, vocal and production tools so you compare products built for the same job.'],
                ['title' => 'Control & export', 'detail' => 'Look for stems, editing, reference audio, arrangement controls and export formats when the output will continue into a production workflow.'],
                ['title' => 'Rights & pricing', 'detail' => 'Commercial-use terms, attribution, royalty rules, generation limits and API availability can matter more than a single demo result.'],
            ],
        ],
        'search-research' => [
            'intro' => 'AI research tools differ in how they search, cite, summarize and work with private documents. Choose according to the evidence standard you need, the sources you trust and whether the result must be reproducible.',
            'factors' => [
                ['title' => 'Source quality', 'detail' => 'Check citation coverage, source visibility and whether the tool distinguishes retrieved evidence from generated explanation.'],
                ['title' => 'Research scope', 'detail' => 'Web search, academic literature, private documents and enterprise knowledge bases require different retrieval capabilities.'],
                ['title' => 'Traceability', 'detail' => 'For important decisions, prefer workflows that let you open the underlying source and verify the answer yourself.'],
            ],
        ],
        'agents-automation' => [
            'intro' => 'AI agents and automation tools can plan, call tools and execute multi-step workflows. Compare them by the actions they are allowed to take, the controls around those actions and how reliably they fit into existing systems.',
            'factors' => [
                ['title' => 'Action scope', 'detail' => 'Understand what the agent can read, write, execute or trigger and where human approval is required.'],
                ['title' => 'Integrations', 'detail' => 'Connectors, APIs and workflow tooling determine whether the agent can operate inside the systems your team already uses.'],
                ['title' => 'Operational control', 'detail' => 'Logs, permissions, retries, monitoring and cost controls are essential when automation moves beyond experimentation.'],
            ],
        ],
        'productivity-office' => [
            'intro' => 'Productivity AI tools support meetings, documents, email, scheduling and everyday knowledge work. The best choice is usually the one that fits the software and collaboration habits already used by the team.',
            'factors' => [
                ['title' => 'Primary workflow', 'detail' => 'Start with the recurring task—meetings, documents, email or planning—rather than choosing a general assistant first.'],
                ['title' => 'Collaboration', 'detail' => 'Check sharing, workspace controls, permissions and integrations if the output is used by more than one person.'],
                ['title' => 'Data handling', 'detail' => 'Review privacy, retention and account controls when the tool will process internal documents or meeting content.'],
            ],
        ],
        'data-analytics' => [
            'intro' => 'AI analytics products range from spreadsheet helpers to coding assistants for data work and full business-intelligence platforms. Compare them by the data sources they can reach, the analyses they can perform and how results are validated.',
            'factors' => [
                ['title' => 'Data access', 'detail' => 'Check supported files, databases, warehouses and connectors before evaluating analysis features.'],
                ['title' => 'Analytical depth', 'detail' => 'Natural-language summaries, SQL generation, notebooks and BI dashboards serve different levels of technical work.'],
                ['title' => 'Verification', 'detail' => 'Prefer workflows that expose calculations, queries or source rows when an analytical result needs to be checked.'],
            ],
        ],
        'marketing-sales' => [
            'intro' => 'Marketing and sales AI tools cover research, content, campaigns, outreach and revenue workflows. Choose by the stage of the funnel you need to improve and the systems where customer data already lives.',
            'factors' => [
                ['title' => 'Funnel stage', 'detail' => 'Prospecting, campaign creation, conversion optimization and sales enablement need different data and automation.'],
                ['title' => 'CRM & channel fit', 'detail' => 'Integrations with CRM, email, ads and analytics often decide whether the tool can be used beyond a demo.'],
                ['title' => 'Governance', 'detail' => 'Review approval workflows, personalization controls and data handling before automating customer-facing activity.'],
            ],
        ],
        'customer-support' => [
            'intro' => 'Customer-support AI can assist agents, answer customers directly or automate service workflows. Compare products by knowledge grounding, escalation controls, channel coverage and how clearly they hand work back to a human.',
            'factors' => [
                ['title' => 'Support role', 'detail' => 'Distinguish agent-assist products from autonomous chatbots and workflow automation before comparing features.'],
                ['title' => 'Knowledge grounding', 'detail' => 'Check how the product uses help-center content, tickets and private knowledge, and whether answers can be traced to sources.'],
                ['title' => 'Escalation & reporting', 'detail' => 'Human handoff, QA, analytics and audit trails matter when support automation is customer-facing.'],
            ],
        ],
        'education-learning' => [
            'intro' => 'Education AI tools include tutoring, study aids, assessment support and content creation. Evaluate them by the learner or educator workflow, the quality of explanations and the controls needed for responsible use.',
            'factors' => [
                ['title' => 'Learning objective', 'detail' => 'Tutoring, revision, assessment and lesson creation require different kinds of guidance and feedback.'],
                ['title' => 'Explanation quality', 'detail' => 'Look for step-by-step reasoning, source support and controls that encourage understanding rather than answer copying.'],
                ['title' => 'Classroom fit', 'detail' => 'Privacy, age suitability, teacher controls, collaboration and institutional deployment can be decisive in formal education.'],
            ],
        ],
    ],
];
