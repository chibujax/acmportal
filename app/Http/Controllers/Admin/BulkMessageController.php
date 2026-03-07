<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use App\Models\User;
use App\Services\EmailService;
use App\Services\SmsService;
use Illuminate\Http\Request;

class BulkMessageController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status', 'all');
        $role   = $request->get('role', 'members');

        $query = User::orderBy('name');

        if (in_array($status, ['active', 'inactive', 'suspended'])) {
            $query->where('status', $status);
        }
        if ($role === 'members') {
            $query->where('role', 'member');
        } elseif ($role === 'admins') {
            $query->whereIn('role', ['admin', 'super_admin']);
        }

        $members   = $query->get();
        $templates = SmsTemplate::orderBy('channel')->orderBy('name')->get();

        return view('admin.messages.index', compact('members', 'templates', 'status', 'role'));
    }

    public function send(Request $request)
    {
        $channel = $request->input('channel', 'sms');

        $rules = [
            'user_ids'   => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
            'channel'    => 'required|in:sms,email',
            'message'    => 'required|string' . ($channel === 'sms' ? '|max:160' : ''),
        ];
        if ($channel === 'email') {
            $rules['subject'] = 'required|string|max:255';
        }
        $request->validate($rules);

        $sent   = 0;
        $failed = 0;

        if ($channel === 'email') {
            $members = User::whereIn('id', $request->user_ids)->whereNotNull('email')->get();
            $email   = app(EmailService::class);
            foreach ($members as $member) {
                $subject = str_replace('{name}', $member->name, $request->subject);
                $body    = str_replace('{name}', $member->name, $request->message);
                $email->send($member->email, $subject, $body) ? $sent++ : $failed++;
            }
            $label = 'Email';
        } else {
            $members = User::whereIn('id', $request->user_ids)->whereNotNull('phone')->get();
            $sms     = app(SmsService::class);
            foreach ($members as $member) {
                $message = str_replace('{name}', $member->name, $request->message);
                $sms->send($member->phone, $message) ? $sent++ : $failed++;
            }
            $label = 'SMS';
        }

        $msg = "{$label} sent to {$sent} member(s).";
        if ($failed > 0) {
            $msg .= " {$failed} failed — check the application logs for details.";
        }

        return back()->with($failed > 0 ? 'warning' : 'success', $msg);
    }
}
