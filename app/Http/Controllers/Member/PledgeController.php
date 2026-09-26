<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\DuesCycle;
use App\Models\MemberPledge;
use Illuminate\Http\Request;

class PledgeController extends Controller
{
    public function store(Request $request, DuesCycle $duesCycle)
    {
        abort_unless($duesCycle->is_pledge_based && $duesCycle->status === 'active', 404);

        $request->validate([
            'pledged_amount'     => 'required|numeric|min:0.01',
            'shared_with_spouse' => 'nullable|boolean',
        ]);

        $user = auth()->user();

        MemberPledge::updateOrCreate(
            ['user_id' => $user->id, 'dues_cycle_id' => $duesCycle->id],
            [
                'pledged_amount'     => $request->pledged_amount,
                'shared_with_spouse' => $request->boolean('shared_with_spouse'),
                'currency'           => $duesCycle->currency,
                'recorded_by'        => $user->id,
            ]
        );

        return back()->with('success', 'Thank you — your pledge has been recorded!');
    }
}
