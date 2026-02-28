<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PendingMember;
use App\Models\RegistrationToken;
use App\Notifications\RegistrationInviteNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use League\Csv\Reader;

class CsvImportController extends Controller
{
    public function showImportForm()
    {
        $batches = PendingMember::select('import_batch', DB::raw('count(*) as total'),
                DB::raw("sum(case when status = 'registered' then 1 else 0 end) as registered"),
                DB::raw("sum(case when status = 'invited' then 1 else 0 end) as invited"))
            ->whereNotNull('import_batch')
            ->groupBy('import_batch')
            ->latest('created_at')
            ->get();

        return view('admin.members.import', compact('batches'));
    }

    public function import(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path   = $request->file('csv_file')->getRealPath();
        $csv    = Reader::createFromPath($path, 'r');
        $csv->setHeaderOffset(0);

        $batch   = 'BATCH-' . now()->format('YmdHis');
        $records = iterator_to_array($csv->getRecords());

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        foreach ($records as $index => $row) {
            $row = array_map('trim', $row);

            $name  = $row['name']  ?? $row['Name']  ?? null;
            $phone = $row['phone'] ?? $row['Phone'] ?? $row['phone_number'] ?? null;

            if (! $name || ! $phone) {
                $errors[] = "Row " . ($index + 2) . ": missing name or phone.";
                $skipped++;
                continue;
            }

            // Normalise phone
            $phone = preg_replace('/\s+/', '', $phone);

            // Skip if already a registered user or pending member
            if (PendingMember::where('phone', $phone)->exists()) {
                $skipped++;
                continue;
            }

            PendingMember::create([
                'name'         => $name,
                'phone'        => $phone,
                'email'        => $row['email'] ?? $row['Email'] ?? null,
                'status'       => 'pending',
                'import_batch' => $batch,
                'imported_by'  => auth()->id(),
            ]);

            $imported++;
        }

        return redirect()->route('admin.members.import')
            ->with('success', "Imported {$imported} members. Skipped {$skipped}.")
            ->with('import_errors', $errors)
            ->with('batch', $batch);
    }

    /**
     * Send registration invite links to pending/failed members in a batch
     * (or to selected individual IDs).
     */
    public function sendInvites(Request $request)
    {
        $request->validate([
            'batch'      => 'nullable|string',
            'member_ids' => 'nullable|array',
            'member_ids.*' => 'integer|exists:pending_members,id',
        ]);

        $query = PendingMember::whereIn('status', ['pending', 'invited']);

        if ($request->batch) {
            $query->where('import_batch', $request->batch);
        } elseif ($request->member_ids) {
            $query->whereIn('id', $request->member_ids);
        }

        $members = $query->get();
        $sent    = 0;

        foreach ($members as $member) {
            $token = RegistrationToken::generate($member);

            $registrationUrl = route('register.form', ['token' => $token->token]);

            // Send via SMS or Email (whichever available)
            try {
                if ($member->email) {
                    // Email notification (requires MAIL config)
                    Notification::route('mail', $member->email)
                        ->notify(new RegistrationInviteNotification($member, $registrationUrl));
                }
                // TODO: integrate SMS gateway for phone invites (Twilio, Vonage)
                // For now, the admin can copy the link manually.

                $member->update([
                    'status'     => 'invited',
                    'invited_at' => now(),
                ]);
                $sent++;
            } catch (\Exception $e) {
                $member->update([
                    'status'         => 'failed',
                    'failure_reason' => $e->getMessage(),
                ]);
            }
        }

        return back()->with('success', "Sent {$sent} registration invite(s).");
    }

    /**
     * Create a single pending member and send them an invite immediately.
     */
    public function inviteSingle(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'phone' => 'required|string|max:30|unique:pending_members,phone|unique:users,phone',
            'email' => 'nullable|email|max:255',
        ]);

        $phone = preg_replace('/\s+/', '', $request->phone);

        $member = PendingMember::create([
            'name'        => $request->name,
            'phone'       => $phone,
            'email'       => $request->email ?: null,
            'status'      => 'pending',
            'imported_by' => auth()->id(),
        ]);

        $token = RegistrationToken::generate($member);
        $registrationUrl = route('register.form', ['token' => $token->token]);

        try {
            if ($member->email) {
                Notification::route('mail', $member->email)
                    ->notify(new RegistrationInviteNotification($member, $registrationUrl));
            }

            $member->update([
                'status'     => 'invited',
                'invited_at' => now(),
            ]);

            return redirect()->route('admin.members.pending')
                ->with('success', "Invite created for {$member->name}. Share the registration link from the pending list.");
        } catch (\Exception $e) {
            $member->update([
                'status'         => 'failed',
                'failure_reason' => $e->getMessage(),
            ]);

            return redirect()->route('admin.members.pending')
                ->with('warning', "Member added but invite notification failed: {$e->getMessage()}. You can copy the link manually.");
        }
    }

    /**
     * Show pending members list with their registration link so admin can share manually.
     */
    public function pendingList()
    {
        $members = PendingMember::with('registrationToken')
            ->whereIn('status', ['pending', 'invited'])
            ->latest()
            ->paginate(20);

        return view('admin.members.pending', compact('members'));
    }
}
