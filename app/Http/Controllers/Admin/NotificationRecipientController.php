<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecipient;
use Illuminate\Http\Request;

class NotificationRecipientController extends Controller
{
    public function index()
    {
        $recipients = NotificationRecipient::orderBy('name')->get();
        return view('admin.notification_recipients.index', compact('recipients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
        ]);

        if (! $request->email && ! $request->phone) {
            return back()->withErrors(['phone' => 'At least one of email or phone is required.'])->withInput();
        }

        NotificationRecipient::create([
            'name'  => $request->name,
            'email' => $request->email ?: null,
            'phone' => $request->phone ?: null,
            'active' => true,
        ]);

        return back()->with('success', 'Recipient added.');
    }

    public function toggle(NotificationRecipient $notificationRecipient)
    {
        $notificationRecipient->update(['active' => ! $notificationRecipient->active]);
        return back()->with('success', 'Recipient updated.');
    }

    public function destroy(NotificationRecipient $notificationRecipient)
    {
        $notificationRecipient->delete();
        return back()->with('success', 'Recipient removed.');
    }
}
