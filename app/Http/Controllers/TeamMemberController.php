<?php

namespace App\Http\Controllers;

use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Laravel\Jetstream\Jetstream;

class TeamMemberController extends Controller
{
    /**
     * Display the team members and roles management page.
     */
    public function index(Request $request, ?Team $team = null)
    {
        $user = $request->user();

        // Resolve active team
        $activeTeam = null;
        if ($team && $team->exists) {
            $activeTeam = $team;
        } elseif ($user->currentTeam) {
            $activeTeam = $user->currentTeam;
        } else {
            $activeTeam = Team::first();
        }

        if (!$activeTeam) {
            abort(404, 'No active tenant workspace found.');
        }

        // Authorize viewing the team
        if (!$user->isSuperAdmin() && !$user->belongsToTeam($activeTeam)) {
            abort(403, 'Unauthorized access to this workspace.');
        }

        $activeTeam->load(['owner', 'users']);

        // Format members list
        $members = collect();

        // 1. Add Owner first
        if ($activeTeam->owner) {
            $members->push([
                'id' => $activeTeam->owner->id,
                'name' => $activeTeam->owner->name,
                'email' => $activeTeam->owner->email,
                'role' => 'Owner',
                'role_key' => 'owner',
                'is_owner' => true,
                'joined_at' => $activeTeam->created_at ? $activeTeam->created_at->format('M d, Y') : 'Original Owner',
            ]);
        }

        // 2. Add other team members
        foreach ($activeTeam->users as $member) {
            $roleKey = $member->membership->role ?? 'editor';
            $roleTitle = $roleKey === 'admin' ? 'Administrator' : 'Editor / Staff';

            $members->push([
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $roleTitle,
                'role_key' => $roleKey,
                'is_owner' => false,
                'joined_at' => $member->membership->created_at ? $member->membership->created_at->format('M d, Y') : 'N/A',
            ]);
        }

        $canManage = $user->isSuperAdmin() || $user->ownsTeam($activeTeam);

        // Defined system roles & permission descriptions
        $availableRoles = [
            [
                'key' => 'admin',
                'name' => 'Administrator',
                'badge' => 'Full Access',
                'description' => 'Can manage inventory, sales, purchases, services, accounting, and add other staff members.',
                'permissions' => [
                    'Manage Products & Inventory',
                    'Process Sales & Invoicing',
                    'Manage Purchase Orders & Vendors',
                    'Double-Entry Accounting & Ledger',
                    'HR, Payroll & Expenses',
                    'Invite & Manage Team Staff',
                ],
            ],
            [
                'key' => 'editor',
                'name' => 'Editor / Staff',
                'badge' => 'Operational',
                'description' => 'Day-to-day operations: creating orders, issuing challans, receiving stock, and tickets.',
                'permissions' => [
                    'Create Sales Orders & Challans',
                    'Receive Stock & Warranties',
                    'Handle Workshop Service Tickets',
                    'Record Customer Collections',
                    'Restricted from Company Settings',
                    'Restricted from Chart of Accounts',
                ],
            ],
        ];

        return Inertia::render('Settings/Team', [
            'team' => [
                'id' => $activeTeam->id,
                'name' => $activeTeam->name,
                'owner_name' => $activeTeam->owner?->name ?? 'System',
                'owner_email' => $activeTeam->owner?->email ?? '',
                'total_members' => $members->count(),
                'created_at' => $activeTeam->created_at ? $activeTeam->created_at->format('M d, Y') : '',
            ],
            'members' => $members,
            'availableRoles' => $availableRoles,
            'canManage' => $canManage,
            'currentUserId' => $user->id,
        ]);
    }

    /**
     * Add a new member to the current team.
     */
    public function addMember(Request $request)
    {
        $user = $request->user();
        $team = $user->currentTeam ?? Team::firstOrFail();

        if (!$user->isSuperAdmin() && !$user->ownsTeam($team)) {
            abort(403, 'Only the organization owner or super admin can add team members.');
        }

        $validated = $request->validate([
            'email' => 'required|email|max:255',
            'role' => 'required|string|in:admin,editor',
            'name' => 'nullable|string|max:255',
        ]);

        $email = strtolower(trim($validated['email']));

        // Check if user is already on the team
        if ($team->owner && strtolower($team->owner->email) === $email) {
            return back()->with('error', 'This user is already the owner of this organization.');
        }

        if ($team->users()->where('email', $email)->exists()) {
            return back()->with('error', 'This user is already a member of this team.');
        }

        // Find or create user
        $memberUser = User::where('email', $email)->first();

        if (!$memberUser) {
            $name = $validated['name'] ?: explode('@', $email)[0];
            $memberUser = User::create([
                'name' => ucwords(str_replace(['.', '_', '-'], ' ', $name)),
                'email' => $email,
                'password' => Hash::make(Str::random(16)),
                'current_team_id' => $team->id,
            ]);
        }

        // Attach to team
        $team->users()->attach($memberUser->id, ['role' => $validated['role']]);

        if ($memberUser->current_team_id === null) {
            $memberUser->forceFill(['current_team_id' => $team->id])->save();
        }

        return back()->with('success', "Member {$memberUser->name} successfully added to {$team->name}.");
    }

    /**
     * Update a team member's role.
     */
    public function updateRole(Request $request, User $user)
    {
        $currentUser = $request->user();
        $team = $currentUser->currentTeam ?? Team::firstOrFail();

        if (!$currentUser->isSuperAdmin() && !$currentUser->ownsTeam($team)) {
            abort(403, 'Only the organization owner or super admin can update roles.');
        }

        $validated = $request->validate([
            'role' => 'required|string|in:admin,editor',
        ]);

        if ($team->owner && $team->owner->id === $user->id) {
            return back()->with('error', 'The team owner role cannot be changed.');
        }

        if (!$team->users()->where('users.id', $user->id)->exists()) {
            return back()->with('error', 'This user is not a member of this team.');
        }

        $team->users()->updateExistingPivot($user->id, ['role' => $validated['role']]);

        $roleTitle = $validated['role'] === 'admin' ? 'Administrator' : 'Editor / Staff';
        return back()->with('success', "Updated role for {$user->name} to {$roleTitle}.");
    }

    /**
     * Remove a member from the team.
     */
    public function removeMember(Request $request, User $user)
    {
        $currentUser = $request->user();
        $team = $currentUser->currentTeam ?? Team::firstOrFail();

        $canRemove = $currentUser->isSuperAdmin() || $currentUser->ownsTeam($team) || $currentUser->id === $user->id;

        if (!$canRemove) {
            abort(403, 'Unauthorized to remove this member.');
        }

        if ($team->owner && $team->owner->id === $user->id) {
            return back()->with('error', 'The team owner cannot be removed.');
        }

        if (!$team->users()->where('users.id', $user->id)->exists()) {
            return back()->with('error', 'This user is not a member of this team.');
        }

        $team->users()->detach($user->id);

        if ($user->current_team_id === $team->id) {
            $user->forceFill(['current_team_id' => $user->personalTeam()?->id])->save();
        }

        return back()->with('success', "{$user->name} was removed from the team.");
    }
}
