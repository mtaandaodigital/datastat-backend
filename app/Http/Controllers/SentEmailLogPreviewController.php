<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SentEmailLog;

class SentEmailLogPreviewController extends Controller
{
    public function preview(Request $request, $id)
    {
        $log = SentEmailLog::find($id);
        if (! $log) {
            abort(404, 'Sent email log not found');
        }

        return view('admin.email_preview', [
            'subject' => $log->subject ?? 'Sent Email',
            'htmlBody' => $log->body_html ?? '<em>No body recorded</em>',
        ]);
    }
}
