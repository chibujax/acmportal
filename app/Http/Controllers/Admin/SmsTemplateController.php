<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $templates = SmsTemplate::orderBy('channel')->orderBy('name')->get();
        return view('admin.sms_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.sms_templates.create');
    }

    public function store(Request $request)
    {
        $channel = $request->input('channel', 'sms');

        $rules = [
            'name'    => 'required|string|max:100|unique:sms_templates,name',
            'channel' => 'required|in:sms,email',
            'body'    => 'required|string' . ($channel === 'sms' ? '|max:160' : ''),
            'subject' => $channel === 'email' ? 'required|string|max:255' : 'nullable|string|max:255',
        ];

        $data = $request->validate($rules);

        SmsTemplate::create($data);

        return redirect()->route('admin.sms-templates.index')
            ->with('success', 'Template created.');
    }

    public function edit(SmsTemplate $smsTemplate)
    {
        return view('admin.sms_templates.edit', compact('smsTemplate'));
    }

    public function update(Request $request, SmsTemplate $smsTemplate)
    {
        $channel = $request->input('channel', 'sms');

        $rules = [
            'name'    => 'required|string|max:100|unique:sms_templates,name,' . $smsTemplate->id,
            'channel' => 'required|in:sms,email',
            'body'    => 'required|string' . ($channel === 'sms' ? '|max:160' : ''),
            'subject' => $channel === 'email' ? 'required|string|max:255' : 'nullable|string|max:255',
        ];

        $data = $request->validate($rules);

        $smsTemplate->update($data);

        return redirect()->route('admin.sms-templates.index')
            ->with('success', 'Template updated.');
    }

    public function destroy(SmsTemplate $smsTemplate)
    {
        $smsTemplate->delete();
        return back()->with('success', 'Template deleted.');
    }
}
