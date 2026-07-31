<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class CoursePdfController extends Controller
{
    /**
     * Generate and return a PDF for the given course.
     */
    public function download(Request $request, Course $course)
    {
        // If the request provides a cutoff date, use it; otherwise default to Dec 31, 2026.
        $cutoff = null;
        if ($request->filled('cutoff')) {
            try {
                $cutoff = \Carbon\Carbon::parse($request->query('cutoff'))->endOfDay();
            } catch (\Throwable $e) {
                $cutoff = null;
            }
        }

        if (empty($cutoff)) {
            try {
                $cutoff = \Carbon\Carbon::create(2026, 12, 31)->endOfDay();
            } catch (\Throwable $e) {
                $cutoff = now()->endOfDay();
            }
        }

        // Build schedules query up to the cutoff
        $schedulesQuery = Schedule::where('course_id', $course->id)
            ->whereDate('start', '<=', $cutoff->toDateString())
            ->orderBy('start');

        // Optional location filter from request
        $location = $request->query('location');
        if (!empty($location)) {
            $schedulesQuery->where('location', $location);
        }

        $schedules = $schedulesQuery->get();

        // Prepare contact details (provided by user)
        $contact = [
            'site_name' => config('app.name', 'DataStat Research'),
            'address' => "College House, Along University Way\nNairobi, Kenya",
            'phones' => ['+254 724 527 104', '+254 734 969 612'],
            'email' => 'info@datastatresearch.com',
            'website' => config('app.url', ''),
        ];

        $data = compact('course', 'schedules', 'contact');

        // Attempt to generate PDF using available bindings. Be defensive to avoid
        // BindingResolutionException when the package is installed but not registered.
        try {
            if (app()->bound('dompdf.wrapper')) {
                $pdf = app('dompdf.wrapper')->loadView('admin.course_pdf', $data);
                $pdf->setPaper('a4');

                return $pdf->download('course-' . ($course->id ?? 'unknown') . '.pdf');
            }

            if (class_exists('\\Barryvdh\\DomPDF\\Facade\\Pdf')) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.course_pdf', $data)
                    ->setPaper('a4')
                    ->setWarnings(false);

                return $pdf->download('course-' . ($course->id ?? 'unknown') . '.pdf');
            }

            if (class_exists('PDF')) {
                $pdf = \PDF::loadView('admin.course_pdf', $data)->setPaper('a4');

                return $pdf->download('course-' . ($course->id ?? 'unknown') . '.pdf');
            }
        } catch (\Illuminate\Contracts\Container\BindingResolutionException $e) {
            // Fall through to HTML preview below if binding is missing or misconfigured.
        } catch (\Throwable $e) {
            // If any other error occurs during PDF generation, log and fall back to HTML preview.
            report($e);
        }

        // If PDF library is not available or fails, return the HTML view so developer can preview
        return view('admin.course_pdf', $data);
    }
}
