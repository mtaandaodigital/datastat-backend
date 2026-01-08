<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\Registrant;
use App\Services\TemplateRenderer;

class EmailTemplatePreviewController extends Controller
{
    public function preview(Request $request, $id)
    {
        $template = EmailTemplate::find($id);
        if (! $template) {
            abort(404, 'Template not found');
        }

        $registrantId = $request->query('registrant');
        $registrant = $registrantId ? Registrant::find($registrantId) : null;

        // Prepare template data similar to the notification
        $schedule = $registrant ? $registrant->schedule : null;
        $course = $schedule ? $schedule->course : null;

        $templateData = [
            'participant_name' => $registrant ? $registrant->full_name : 'Participant Name',
            'training_date' => $schedule ? \Carbon\Carbon::parse($schedule->start)->format('M d, Y') . ' – ' . \Carbon\Carbon::parse($schedule->end)->format('M d, Y') : 'TBD',
            'training_location' => $schedule ? $schedule->location : 'N/A',
            'start_date' => $schedule ? \Carbon\Carbon::parse($schedule->start)->format('M d, Y') : 'TBD',
            'end_date' => $schedule ? \Carbon\Carbon::parse($schedule->end)->format('M d, Y') : 'TBD',
            'course_title' => $course ? $course->title : ($registrant->title_course ?? 'Training Program'),
            'coordinator_name' => config('app.coordinator_name', 'Sammy Gathuru'),
        ];

        $rendered = TemplateRenderer::renderFromRecord($template, $templateData);

        // Render an admin preview that includes subject and the email blade view
        return view('admin.email_preview', [
            'subject' => $rendered['subject'],
            'htmlBody' => $rendered['body'],
        ]);
    }
}
