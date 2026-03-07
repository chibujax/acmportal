<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberChild;
use App\Models\MemberRelationship;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChildrenController extends Controller
{
    public function index(Request $request)
    {
        $query = MemberChild::with(['father', 'mother', 'addedBy']);

        if ($request->search) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%$s%")
                  ->orWhere('last_name', 'like', "%$s%")
                  ->orWhereHas('father', fn($q2) => $q2->where('name', 'like', "%$s%"))
                  ->orWhereHas('mother', fn($q2) => $q2->where('name', 'like', "%$s%"));
            });
        }

        $children = $query->orderBy('last_name')->orderBy('first_name')->paginate(25)->withQueryString();

        return view('admin.relationships.children', compact('children'));
    }

    public function create()
    {
        $members = User::orderBy('name')->get(['id', 'name']);
        $couples = MemberRelationship::where('relationship_type', 'spouse')
            ->get(['member_id_1', 'member_id_2']);

        return view('admin.relationships.children-form', compact('members', 'couples'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender'        => 'nullable|in:male,female',
            'father_id'     => 'nullable|exists:users,id',
            'mother_id'     => 'nullable|exists:users,id',
            'notes'         => 'nullable|string|max:500',
        ]);

        if (empty($data['father_id']) && empty($data['mother_id'])) {
            throw ValidationException::withMessages([
                'father_id' => 'At least one parent (father or mother) must be linked.',
            ]);
        }

        $data['added_by'] = auth()->id();

        MemberChild::create($data);

        return redirect()->route('admin.children.index')
            ->with('success', 'Child record added successfully.');
    }

    public function edit(MemberChild $child)
    {
        $members = User::orderBy('name')->get(['id', 'name']);
        $couples = MemberRelationship::where('relationship_type', 'spouse')
            ->get(['member_id_1', 'member_id_2']);

        return view('admin.relationships.children-form', compact('child', 'members', 'couples'));
    }

    public function update(Request $request, MemberChild $child)
    {
        $data = $request->validate([
            'first_name'    => 'required|string|max:100',
            'last_name'     => 'required|string|max:100',
            'date_of_birth' => 'nullable|date|before:today',
            'gender'        => 'nullable|in:male,female',
            'father_id'     => 'nullable|exists:users,id',
            'mother_id'     => 'nullable|exists:users,id',
            'notes'         => 'nullable|string|max:500',
        ]);

        if (empty($data['father_id']) && empty($data['mother_id'])) {
            throw ValidationException::withMessages([
                'father_id' => 'At least one parent (father or mother) must be linked.',
            ]);
        }

        $child->update($data);

        return redirect()->route('admin.children.index')
            ->with('success', 'Child record updated.');
    }

    public function destroy(MemberChild $child)
    {
        $child->delete();
        return back()->with('success', 'Child record deleted.');
    }
}
