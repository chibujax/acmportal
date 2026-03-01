<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SmsTemplate;
use Illuminate\Http\Request;

class SmsTemplateController extends Controller
{
    public function index()
    {
        $templates = SmsTemplate::orderBy('name')->get();
        return view('admin.sms_templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.sms_templates.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:sms_templates,name',
            'body' => 'required|string|max:160',
        ]);

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
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:sms_templates,name,' . $smsTemplate->id,
            'body' => 'required|string|max:160',
        ]);

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
