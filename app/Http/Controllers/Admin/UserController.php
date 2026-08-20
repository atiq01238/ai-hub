<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\User;
use App\Services\Frontend\CommunityModerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query()
            ->with('roleModel')
            ->withCount(['reviews', 'submissions', 'reportsReceived', 'communityComments']);

        if ($search = trim((string) $request->query('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($access = $request->query('access')) {
            if (in_array($access, ['admin', 'user'], true)) {
                $query->where('role', $access);
            }
        }

        if ($roleId = $request->integer('role_id')) {
            $query->where('role_id', $roleId);
        }

        if ($trust = $request->query('community_trust')) {
            if (in_array($trust, ['normal', 'trusted', 'restricted'], true)) {
                $query->where('community_trust_level', $trust);
            }
        }

        if ($status = $request->query('status')) {
            if (in_array($status, ['active', 'suspended'], true)) {
                $query->where('status', $status);
            }
        }

        match ($request->query('sort')) {
            'oldest' => $query->oldest(),
            'name' => $query->orderBy('name'),
            'most-active' => $query->orderByDesc('reviews_count')->orderByDesc('submissions_count'),
            default => $query->latest(),
        };

        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'admins' => User::where('role', 'admin')->count(),
        ];

        return view('users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'roles' => Role::orderBy('name')->get(),
            'stats' => $stats,
        ]);
    }

    public function show(int $id)
    {
        $user = User::query()
            ->with(['roleModel', 'suspendedBy'])
            ->withCount(['reviews', 'submissions', 'reportsReceived', 'reportsFiled', 'communityComments'])
            ->findOrFail($id);

        return view('users.show', [
            'user' => $user,
            'recentReviews' => $user->reviews()->with('tool')->latest()->take(5)->get(),
            'recentSubmissions' => $user->submissions()->latest()->take(5)->get(),
            'recentReports' => $user->reportsReceived()->with('reporter')->latest()->take(5)->get(),
            'roles' => Role::orderBy('name')->get(),
            'communityStats' => app(CommunityModerationService::class)->stats($user),
        ]);
    }

    public function suspend(Request $request, int $id)
    {
        $data = $request->validate([
            'suspension_reason' => ['required', 'string', 'max:1000'],
            'suspended_until' => ['nullable', 'date', 'after:now'],
        ]);

        abort_if($request->user()->id === $id, 422, 'You cannot suspend your own account.');

        $user = User::findOrFail($id);

        if ($user->isSuperAdmin()) {
            abort_unless($this->hasOtherActiveSuperAdmin($user), 422, 'The final active Super Admin cannot be suspended.');
        }

        if ($user->role === 'admin') {
            $otherActiveAdmins = User::where('role', 'admin')
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();

            abort_if($otherActiveAdmins === 0, 422, 'The final active admin cannot be suspended.');
        }

        $user->forceFill([
            'status' => 'suspended',
            'suspension_reason' => $data['suspension_reason'],
            'suspended_at' => now(),
            'suspended_until' => $data['suspended_until'] ?? null,
            'suspended_by' => $request->user()->id,
        ])->save();

        // Revoke database-backed sessions immediately. The active-account
        // middleware protects other session drivers on their next request.
        DB::table('sessions')->where('user_id', $user->id)->delete();

        $this->logAction($request->user()->id, 'suspended', $user, $data['suspension_reason']);

        return back()->with('status', "{$user->name} has been suspended and signed out.");
    }

    public function activate(Request $request, int $id)
    {
        $user = User::findOrFail($id);
        $user->forceFill([
            'status' => 'active',
            'suspension_reason' => null,
            'suspended_at' => null,
            'suspended_until' => null,
            'suspended_by' => null,
        ])->save();

        $this->logAction($request->user()->id, 'activated', $user);

        return back()->with('status', "{$user->name} has been activated.");
    }

    public function assignRole(Request $request, int $id)
    {
        $data = $request->validate(['role_id' => ['required', 'exists:roles,id']]);

        $user = User::findOrFail($id);
        $targetRole = Role::findOrFail($data['role_id']);

        abort_if($user->role !== 'admin', 422, 'Permission roles can only be assigned to Administrator accounts.');
        abort_if($request->user()->is($user), 422, 'You cannot change your own permission role.');
        abort_if($user->isSuperAdmin() && ! $request->user()->isSuperAdmin(), 403, 'Only a Super Admin can modify another Super Admin.');
        abort_if($targetRole->isSystemRole() && ! $request->user()->isSuperAdmin(), 403, 'Only a Super Admin can assign the Super Admin role.');
        if ($user->isSuperAdmin() && ! $targetRole->isSystemRole()) {
            abort_unless($this->hasOtherActiveSuperAdmin($user), 422, 'The final active Super Admin cannot be reassigned.');
        }

        $user->update(['role_id' => $targetRole->id]);

        $this->logAction($request->user()->id, 'role_updated', $user, $targetRole->name);

        return back()->with('status', "Permission role updated to {$targetRole->name}.");
    }

    public function updateAccess(Request $request, int $id)
    {
        $data = $request->validate([
            'access_level' => ['required', 'in:admin,user'],
            'role_id' => ['nullable', 'required_if:access_level,admin', 'exists:roles,id'],
        ]);

        $user = User::findOrFail($id);
        $targetRole = $data['access_level'] === 'admin' ? Role::findOrFail($data['role_id']) : null;

        abort_if($request->user()->is($user), 422, 'You cannot change your own access level.');
        abort_if($user->isSuperAdmin() && ! $request->user()->isSuperAdmin(), 403, 'Only a Super Admin can modify another Super Admin.');
        abort_if($targetRole?->isSystemRole() && ! $request->user()->isSuperAdmin(), 403, 'Only a Super Admin can assign the Super Admin role.');
        if ($user->isSuperAdmin() && ! $targetRole?->isSystemRole()) {
            abort_unless($this->hasOtherActiveSuperAdmin($user), 422, 'The final active Super Admin cannot be demoted or reassigned.');
        }

        if ($user->role === 'admin' && $data['access_level'] === 'user') {
            $otherActiveAdmins = User::where('role', 'admin')
                ->where('status', 'active')
                ->where('id', '!=', $user->id)
                ->count();

            abort_if($otherActiveAdmins === 0, 422, 'The final active admin cannot be demoted.');
        }

        $user->forceFill([
            'role' => $data['access_level'],
            'role_id' => $data['access_level'] === 'admin' ? $targetRole?->id : null,
        ])->save();

        DB::table('sessions')->where('user_id', $user->id)->delete();
        $this->logAction($request->user()->id, 'access_updated', $user, $data['access_level']);

        return back()->with('status', "{$user->name}'s access level was updated and active sessions were revoked.");
    }


    public function updateCommunityTrust(Request $request, int $id)
    {
        abort_unless(
            $request->user()?->role === 'admin'
            && $request->user()?->status === 'active'
            && $request->user()?->canAccessModule('Users', 'Edit'),
            403
        );

        $data = $request->validate([
            'community_trust_level' => ['required', 'in:normal,trusted,restricted'],
            'community_restriction_reason' => ['nullable', 'required_if:community_trust_level,restricted', 'string', 'max:1000'],
        ]);

        $user = User::findOrFail($id);

        $user->forceFill([
            'community_trust_level' => $data['community_trust_level'],
            'community_trusted_at' => $data['community_trust_level'] === 'trusted' ? now() : null,
            'community_restricted_at' => $data['community_trust_level'] === 'restricted' ? now() : null,
            'community_restriction_reason' => $data['community_trust_level'] === 'restricted'
                ? trim((string) $data['community_restriction_reason'])
                : null,
            'community_trust_updated_by' => $request->user()->id,
        ])->save();

        $this->logAction(
            $request->user()->id,
            'community_trust_updated',
            $user,
            $data['community_trust_level']
        );

        return back()->with('status', "{$user->name}'s community trust level is now " . ucfirst($data['community_trust_level']) . '.');
    }


    private function hasOtherActiveSuperAdmin(User $user): bool
    {
        return User::query()
            ->where('id', '!=', $user->id)
            ->where('role', 'admin')
            ->where('status', 'active')
            ->whereHas('roleModel', fn ($q) => $q->where('slug', 'super-admin'))
            ->exists();
    }

    private function logAction(int $actorId, string $action, User $subject, ?string $detail = null): void
    {
        ActivityLog::create([
            'user_id' => $actorId,
            'action' => $action,
            'subject_type' => User::class,
            'subject_id' => $subject->id,
            'description' => ucfirst(str_replace('_', ' ', $action)) . " user \"{$subject->name}\"" . ($detail ? ": {$detail}" : ''),
        ]);
    }
}