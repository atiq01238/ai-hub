<div class="drawer" id="newsDrawer">
    <div class="modal-head">
        <h3 style="margin:0; font-size:15px;">News Detail &amp; Verification</h3>
        <button class="icon-btn" onclick="document.getElementById('newsDrawer').classList.remove('is-open')"><i data-lucide="x"></i></button>
    </div>
    <div class="modal-body">
        <div class="flex items-center gap-8" style="margin-bottom:12px; flex-wrap:wrap;">
            <span class="badge badge-neg">Breaking News</span>
            <span class="badge badge-pos">Positive</span>
            <span class="cell-sub">The Information · 12 min ago</span>
        </div>
        <h2 style="font-size:19px; margin:0 0 14px;">OpenAI announces GPT-5.2 Turbo with native agent orchestration</h2>

        <div class="form-section">
            <div class="form-section__title">Summary</div>
            <p class="text-sub" style="font-size:13px; line-height:1.6;">
                OpenAI unveiled GPT-5.2 Turbo, its newest flagship model, introducing native multi-agent
                task delegation and a 2M token context window. The release targets enterprise workflows
                that require long-running, multi-step automation across tools and data sources.
            </p>
            <div style="background:var(--surface-2); border-radius:var(--radius-sm); padding:12px 14px; font-size:12.5px; color:var(--brand-3); margin-top:10px;">
                <i data-lucide="lightbulb" style="width:13px;height:13px; vertical-align:-2px;"></i>
                <b>Why it matters:</b> Signals a broader industry shift toward agentic-first product design.
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Classification</div>
            <div class="grid-2">
                <div><div class="cell-sub">Sentiment</div><div style="font-weight:600;">Positive</div></div>
                <div><div class="cell-sub">Importance Score</div><x-score-meter :value="96" /></div>
                <div><div class="cell-sub">Related Company</div><div style="font-weight:600;">OpenAI</div></div>
                <div><div class="cell-sub">Related Tools</div><div style="font-weight:600;">ChatGPT</div></div>
            </div>
            <div style="margin-top:12px;">
                <div class="cell-sub" style="margin-bottom:6px;">Tags</div>
                <div class="flex gap-8" style="flex-wrap:wrap;">
                    <span class="badge badge-neutral">agents</span>
                    <span class="badge badge-neutral">llm</span>
                    <span class="badge badge-neutral">enterprise</span>
                    <span class="badge badge-neutral">context-window</span>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Source Verification</div>
            <div class="grid-2" style="margin-bottom:12px;">
                <div><div class="cell-sub">Source Reliability</div><x-score-meter :value="94" /></div>
                <div><div class="cell-sub">Sources Reporting</div><div style="font-weight:600;">7 outlets</div></div>
                <div><div class="cell-sub">Publication Consistency</div><div style="font-weight:600;">High</div></div>
                <div><div class="cell-sub">Status</div><span class="badge badge-pos">Verified</span></div>
            </div>
            <div class="cell-sub" style="margin-bottom:6px;">Original Source</div>
            <div class="card card-pad" style="margin-bottom:10px; padding:10px 12px; font-size:12.5px;">
                theinformation.com/articles/openai-gpt-5-2-turbo <i data-lucide="external-link" style="width:12px;height:12px;"></i>
            </div>
            <div class="cell-sub" style="margin-bottom:6px;">Secondary Sources</div>
            <div class="flex gap-8" style="flex-wrap:wrap;">
                <span class="badge badge-neutral">TechCrunch</span>
                <span class="badge badge-neutral">The Verge</span>
                <span class="badge badge-neutral">Ars Technica</span>
                <span class="badge badge-neutral">+4 more</span>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section__title">Related News</div>
            <div style="font-size:13px; color:var(--text-md); padding:8px 0; border-bottom:1px solid var(--border-soft);">OpenAI's agent framework adoption grows 3x quarter over quarter</div>
            <div style="font-size:13px; color:var(--text-md); padding:8px 0;">Enterprises weigh switching costs as agent platforms mature</div>
        </div>
    </div>
    <div class="modal-foot" style="justify-content:space-between;">
        <div class="flex gap-8">
            <button class="btn btn-danger btn-sm"><i data-lucide="x"></i> Reject</button>
            <button class="btn btn-secondary btn-sm"><i data-lucide="pencil"></i> Edit</button>
        </div>
        <div class="flex gap-8">
            <button class="btn btn-secondary btn-sm"><i data-lucide="file-text"></i> Create Article</button>
            <button class="btn btn-primary btn-sm"><i data-lucide="badge-check"></i> Verify</button>
        </div>
    </div>
</div>
<style>
#newsDrawer{ display:none; }
#newsDrawer.is-open{ display:block; }
</style>
