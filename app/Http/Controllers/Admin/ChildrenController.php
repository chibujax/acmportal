<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MemberChild;
use Illuminate\Http\Request;

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

    public function destroy(MemberChild $child)
    {
        $child->delete();
        return back()->with('success', 'Child record deleted.');
    }
}
