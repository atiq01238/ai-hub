<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\Submission;
use App\Models\User;
use App\Mail\ReportStatusMail;
use App\Mail\SubmissionStatusMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UsersCommunityWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_account_cannot_sign_in(): void
    {
        $user = User::factory()->create([
            'password' => 'password123',
            'status' => 'suspended',
        ]);

        $this->post(route('login.authenticate'), [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_public_contributor_can_submit_a_tool_suggestion(): void
    {
        $this->post(route('submissions.store'), [
            'submission_type' => 'tool',
            'tool_name' => 'Community AI',
            'submitted_by_email' => 'contributor@example.com',
            'website' => 'https://example.com',
            'category' => 'Productivity',
            'description' => 'A useful AI productivity assistant.',
            'company_name' => '',
        ])->assertRedirect(route('submissions.create'));

        $this->assertDatabaseHas('submissions', [
            'tool_name' => 'Community AI',
            'status' => 'pending',
        ]);
    }

    public function test_admin_approval_creates_a_draft_tool(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $submission = Submission::create([
            'submission_type' => 'tool',
            'tool_name' => 'Approved Community AI',
            'submitted_by_email' => 'contributor@example.com',
            'website' => 'https://approved.example.com',
            'description' => 'Ready for verification.',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.submissions.approve', $submission->id), ['admin_notes' => 'Verified source.'])
            ->assertRedirect();

        $this->assertDatabaseHas('tools', [
            'name' => 'Approved Community AI',
            'status' => 'draft',
        ]);
        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
            'status' => 'approved',
            'reviewed_by' => $admin->id,
        ]);
        Mail::assertQueued(SubmissionStatusMail::class, fn ($mail) => $mail->hasTo('contributor@example.com'));
    }

    public function test_member_can_report_another_user_and_admin_can_resolve_it(): void
    {
        Mail::fake();
        $reporter = User::factory()->create(['status' => 'active']);
        $reportedUser = User::factory()->create(['status' => 'active']);

        $this->actingAs($reporter)
            ->post(route('reports.store'), [
                'reportable_type' => 'user',
                'reportable_id' => $reportedUser->id,
                'reason' => 'impersonation',
                'description' => 'This profile is copying another identity.',
            ])->assertRedirect();

        $report = Report::firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.community.reports.status', $report->id), [
                'status' => 'resolved',
                'priority' => 'high',
                'resolution_note' => 'Identity checked and the account was actioned.',
            ])->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'id' => $report->id,
            'status' => 'resolved',
            'resolved_by' => $admin->id,
        ]);
        Mail::assertQueued(ReportStatusMail::class, fn ($mail) => $mail->hasTo($reporter->email));
    }

    public function test_legacy_public_review_moderation_route_is_not_exposed(): void
    {
        $this->post('/reviews/1/approve')->assertNotFound();
        $this->post('/reviews/1/flag')->assertNotFound();
        $this->delete('/reviews/1')->assertNotFound();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'role_id' => null,
        ]);
    }
}
