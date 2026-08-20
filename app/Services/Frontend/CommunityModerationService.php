<?php

namespace App\Services\Frontend;

use App\Models\CommunityComment;
use App\Models\User;
use Illuminate\Support\Str;

class CommunityModerationService
{
    public function decide(User $user, string $body): array
    {
        $body = trim($body);
        $suspicion = $this->suspicionReason($user, $body);

        if ($user->isCommunityRestricted()) {
            return $this->pending(
                $user->community_restriction_reason
                    ? 'Restricted account: ' . Str::limit($user->community_restriction_reason, 180)
                    : 'Restricted account requires moderation.'
            );
        }

        if ($suspicion) {
            return $this->pending($suspicion);
        }

        $this->promoteIfEligible($user);

        if ($user->fresh()->isCommunityTrusted()) {
            return [
                'status' => 'published',
                'auto_published' => true,
                'reason' => 'Trusted member · clean contribution',
            ];
        }

        return $this->pending(
            'New/normal member: first '
            . (int) config('community.approved_before_trusted', 3)
            . ' approved contributions require moderation.'
        );
    }

    public function afterAdminModeration(CommunityComment $comment, string $oldStatus): void
    {
        $user = $comment->user;

        if (! $user || $user->community_trust_level === 'restricted') {
            return;
        }

        if ($comment->status === 'spam' && $user->community_trust_level === 'trusted') {
            $user->forceFill([
                'community_trust_level' => 'normal',
                'community_trusted_at' => null,
            ])->save();

            return;
        }

        if ($comment->status === 'published' && $oldStatus !== 'published') {
            $this->promoteIfEligible($user);
        }
    }

    public function promoteIfEligible(User $user): bool
    {
        if ($user->community_trust_level !== 'normal') {
            return false;
        }

        $required = (int) config('community.approved_before_trusted', 3);

        $publishedCount = CommunityComment::query()
            ->where('user_id', $user->id)
            ->where('status', 'published')
            ->count();

        $spamCount = CommunityComment::query()
            ->where('user_id', $user->id)
            ->where('status', 'spam')
            ->count();

        if ($publishedCount < $required || $spamCount > 0) {
            return false;
        }

        $user->forceFill([
            'community_trust_level' => 'trusted',
            'community_trusted_at' => now(),
            'community_restricted_at' => null,
            'community_restriction_reason' => null,
        ])->save();

        return true;
    }

    public function stats(User $user): array
    {
        $base = CommunityComment::query()->where('user_id', $user->id);

        return [
            'published' => (clone $base)->where('status', 'published')->count(),
            'pending' => (clone $base)->where('status', 'pending')->count(),
            'hidden' => (clone $base)->where('status', 'hidden')->count(),
            'spam' => (clone $base)->where('status', 'spam')->count(),
        ];
    }

    private function suspicionReason(User $user, string $body): ?string
    {
        if ($body === '') {
            return 'Empty contribution.';
        }

        $linkCount = preg_match_all(
            '/(?:https?:\/\/|www\.)[^\s]+/iu',
            $body
        );

        if ($linkCount > (int) config('community.max_links', 0)) {
            return 'Contains an external link.';
        }

        $lower = Str::lower($body);

        foreach ((array) config('community.spam_phrases', []) as $phrase) {
            if ($phrase !== '' && Str::contains($lower, Str::lower($phrase))) {
                return 'Matched a spam-risk phrase.';
            }
        }

        if (mb_strlen($body) >= (int) config('community.long_comment_threshold', 1600)) {
            return 'Long contribution requires a quick moderation check.';
        }

        if (preg_match('/(.)\1{8,}/u', $body)) {
            return 'Contains repeated-character spam pattern.';
        }

        $letters = preg_replace('/[^A-Za-z]/', '', $body) ?: '';
        if (strlen($letters) >= 30) {
            $upper = preg_replace('/[^A-Z]/', '', $letters) ?: '';
            $ratio = strlen($upper) / max(1, strlen($letters));

            if ($ratio >= (float) config('community.uppercase_ratio_threshold', 0.72)) {
                return 'Unusually high uppercase ratio.';
            }
        }

        $duplicateSince = now()->subHours(
            (int) config('community.duplicate_window_hours', 24)
        );

        $duplicate = CommunityComment::query()
            ->where('user_id', $user->id)
            ->where('body', $body)
            ->where('created_at', '>=', $duplicateSince)
            ->exists();

        if ($duplicate) {
            return 'Duplicate recent contribution.';
        }

        return null;
    }

    private function pending(string $reason): array
    {
        return [
            'status' => 'pending',
            'auto_published' => false,
            'reason' => $reason,
        ];
    }
}
