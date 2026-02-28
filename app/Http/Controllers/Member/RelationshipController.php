<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\MemberChild;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Http\Request;

class RelationshipController extends Controller
{
    public function index()
    {
        $user     = auth()->user();
        $spouse   = $user->spouse();
        $children = $user->visibleChildren();

        return view('member.relationships', compact('user', 'spouse', 'children'));
    }

    // ── Spouse ────────────────────────────────────────────────

    public function searchSpouse(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $userId = auth()->id();

        $results = User::where('role', 'member')
            ->where('status', 'active')
            ->where('id', '!=', $userId)
            ->where(fn($query) => $query->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%"))
            ->whereNotIn('id', fn($sub) => $sub->select('member_id_1')->from('member_relationships')->where('relationship_type', 'spouse'))
            ->whereNotIn('id', fn($sub) => $sub->select('member_id_2')->from('member_relationships')->where('relationship_type', 'spouse'))
            ->limit(8)
            ->get(['id', 'name', 'phone']);

        // Filter out members already in a spouse relationship
        $results = $results->filter(function ($member) {
            return ! $member->hasSpouse();
        })->values();

        return response()->json($results);
    }

    public function linkSpouse(Request $request)
    {
        $user = auth()->user();

        if ($user->hasSpouse()) {
            return back()->withErrors(['spouse' => 'You already have a linked spouse. Unlink first.']);
        }

        $request->validate([
            'spouse_id' => 'required|exists:users,id|different:' . $user->id,
        ]);

        $spouse = User::findOrFail($request->spouse_id);

        if ($spouse->hasSpouse()) {
            return back()->withErrors(['spouse' => "{$spouse->name} already has a linked spouse."]);
        }

        MemberRelationship::create([
            'member_id_1'       => $user->id,
            'member_id_2'       => $spouse->id,
            'relationship_type' => 'spouse',
            'created_by'        => $user->id,
            'created_at'        => now(),
        ]);

        return back()->with('success', "You are now linked with {$spouse->name}.");
    }

    public function unlinkSpouse()
    {
        $user = auth()->user();
        $rel  = $user->spouseRelationship();

        if (! $rel) {
            return back()->withErrors(['spouse' => 'No spouse link found.']);
        }

        $spouseName = $rel->otherMember($user->id)?->name ?? 'your spouse';
        $rel->delete();

        return back()->with('success', "Spouse link with {$spouseName} has been removed.");
    }

    // ── Children ──────────────────────────────────────────────

    public function addChild(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender'        => 'nullable|in:male,female',
            'notes'         => 'nullable|string|max:500',
        ]);

        // Automatically assign as father or mother based on gender — 
        // since we don't store gender, use the adding member as the parent
        // and auto-link the spouse if present.
        $spouse    = $user->spouse();
        $fatherId  = null;
        $motherId  = null;

        // Determine which parent slot to fill based on who is adding
        // We look at existing children to guess — or just let the system
        // auto-assign. For simplicity, the form lets user pick their role.
        $role = $request->get('parent_role', 'father'); // 'father' or 'mother'

        if ($role === 'father') {
            $fatherId = $user->id;
            if ($spouse) {
                $motherId = $spouse->id;
            }
        } else {
            $motherId = $user->id;
            if ($spouse) {
                $fatherId = $spouse->id;
            }
        }

        MemberChild::create([
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender'        => $data['gender'] ?? null,
            'notes'         => $data['notes'] ?? null,
            'father_id'     => $fatherId,
            'mother_id'     => $motherId,
            'added_by'      => $user->id,
        ]);

        return back()->with('success', 'Child added successfully.');
    }

    public function removeChild(MemberChild $child)
    {
        $user = auth()->user();
        $spouse = $user->spouse();

        // Only the father, mother, or admin can remove
        $canRemove = $child->father_id === $user->id
            || $child->mother_id === $user->id
            || ($spouse && ($child->father_id === $spouse->id || $child->mother_id === $spouse->id));

        if (! $canRemove) {
            abort(403);
        }

        $child->delete();

        return back()->with('success', 'Child record removed.');
    }
}
