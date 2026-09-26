<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Http\Request;

class SpouseController extends Controller
{
    public function index(Request $request)
    {
        $query = MemberRelationship::with(['member1', 'member2'])
            ->where('relationship_type', 'spouse');

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->whereHas('member1', fn($q2) => $q2->where('name', 'like', "%$s%"))
                  ->orWhereHas('member2', fn($q2) => $q2->where('name', 'like', "%$s%"));
            });
        }

        $relationships = $query->orderByDesc('created_at')->paginate(25)->withQueryString();

        return view('admin.relationships.spouses', compact('relationships'));
    }

    /**
     * AJAX member search for the link form. Unlike the member self-service
     * search, this deliberately does NOT exclude already-linked members —
     * admins need to be able to select either side of an existing (possibly
     * wrong) pair in order to correct it.
     */
    public function search(Request $request)
    {
        $q = $request->get('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $results = User::where('role', '!=', 'super_admin')
            ->where('status', 'active')
            ->where(fn($query) => $query->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%"))
            ->orderBy('name')
            ->limit(8)
            ->get(['id', 'name', 'phone']);

        return response()->json($results);
    }

    public function link(Request $request)
    {
        $request->validate([
            'member_id' => 'required|exists:users,id|different:spouse_id',
            'spouse_id' => 'required|exists:users,id',
        ]);

        $member = User::findOrFail($request->member_id);
        $spouse = User::findOrFail($request->spouse_id);

        if ($member->hasSpouse()) {
            return back()->withErrors(['member_id' => "{$member->name} already has a linked spouse. Unlink first."]);
        }

        if ($spouse->hasSpouse()) {
            return back()->withErrors(['spouse_id' => "{$spouse->name} already has a linked spouse. Unlink first."]);
        }

        MemberRelationship::create([
            'member_id_1'       => $member->id,
            'member_id_2'       => $spouse->id,
            'relationship_type' => 'spouse',
            'created_by'        => auth()->id(),
            'created_at'        => now(),
        ]);

        return redirect()->route('admin.spouses.index')
            ->with('success', "Linked {$member->name} and {$spouse->name} as spouses.");
    }

    public function unlink(MemberRelationship $relationship)
    {
        abort_unless($relationship->relationship_type === 'spouse', 404);

        $relationship->delete();

        return back()->with('success', 'Spouse link removed.');
    }
}
