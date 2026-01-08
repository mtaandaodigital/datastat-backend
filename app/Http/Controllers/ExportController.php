<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Models\Lead;
use App\Models\News;
use App\Models\Registrant;
use App\Models\Trainer;
use Illuminate\Http\Request;

class ExportController extends Controller
{
    public function exportCourses()
    {
        // Increase memory limit for large exports
        ini_set('memory_limit', '1G');
        set_time_limit(300); // 5 minutes
        
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID', 'Title', 'Category', 'Start Date', 'End Date', 'Cutoff Date',
                'Duration (Days)', 'Location', 'Status', 'Course Status', 'KES Price', 'USD Price',
                'EUR Price', 'GBP Price', 'Software', 'Introduction', 'Body', 'Submitted Time'
            ]);
            
            // Sanitize helper for HTML to CSV-safe text
            $sanitize = function (?string $html): string {
                if (!$html) return '';
                // Convert common block breaks to spaces to avoid CSV newlines
                $text = preg_replace('/<\s*br\s*\/?\s*>/i', ' ', $html);
                $text = preg_replace('/<\/(p|div|li)\s*>/i', ' ', $text);
                // Strip remaining tags
                $text = strip_tags($text);
                // Decode HTML entities
                $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                // Collapse whitespace
                $text = preg_replace('/\s+/u', ' ', $text);
                return trim($text);
            };

            // Process data in chunks to avoid memory issues
            Course::orderBy('id', 'desc')->chunk(100, function ($courses) use ($file, $sanitize) {
                foreach ($courses as $course) {
                    fputcsv($file, [
                        $course->id,
                        $course->title,
                        $course->category,
                        $course->start_date,
                        $course->end,
                        $course->cut_of_date,
                        $course->no_of_days,
                        $course->location,
                        $course->schedule_status ? 'Active' : 'Inactive',
                        $course->course_status,
                        $course->fee_kes,
                        $course->fee_usd,
                        $course->fee_euro,
                        $course->fee_pound,
                        $course->software,
                        $sanitize($course->introduction ?? ''),
                        $sanitize($course->body ?? ''),
                        $course->submitted_time,
                    ]);
                }
            });
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="courses_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ]);
    }

    public function exportUsers()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
        
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID', 'First Name', 'Last Name', 'Full Name', 'Email', 'Phone', 
                'Organization', 'Access Level', 'User Level', 'Status', 'Created At', 'Last Login'
            ]);
            
            // Process data in chunks to avoid memory issues
            User::orderBy('usermanagementid', 'desc')->chunk(100, function ($users) use ($file) {
                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->usermanagementid,
                        $user->firstname,
                        $user->lastname,
                        $user->full_name,
                        $user->email,
                        $user->phone,
                        $user->organization,
                        $user->accesslevel == 1 ? 'Full Access' : 'No Access',
                        $user->user_level == 1 ? 'Super Admin' : 'Regular User',
                        $user->login_active_status ? 'Active' : 'Inactive',
                        $user->created_at,
                        $user->updated_at,
                    ]);
                }
            });
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ]);
    }

    public function exportLeads()
    {
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'Unauthorized');
        }
        
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID', 'Name', 'Email', 'Phone', 'Organization', 'Interest',
                'Source', 'Status', 'Assigned To', 'Potential Value', 'Country', 'City',
                'Created At', 'Last Contacted', 'Converted At', 'Notes'
            ]);
            
            // Process data in chunks to avoid memory issues
            Lead::with('assignedUser')->orderBy('id', 'desc')->chunk(100, function ($leads) use ($file) {
                foreach ($leads as $lead) {
                    fputcsv($file, [
                        $lead->id,
                        $lead->name,
                        $lead->email,
                        $lead->phone,
                        $lead->organization,
                        $lead->interest,
                        $lead->source,
                        $lead->status,
                        $lead->assignedUser?->full_name,
                        $lead->potential_value,
                        $lead->country,
                        $lead->city,
                        $lead->created_at,
                        $lead->last_contacted_at,
                        $lead->converted_at,
                        $lead->notes ?? '',
                    ]);
                }
            });
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="leads_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ]);
    }

    public function exportNews()
    {
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID', 'Title', 'Category', 'Author', 'Status', 'Featured',
                'Published At', 'Introduction', 'Reading Time', 'Created At'
            ]);
            
            // Process data in chunks to avoid memory issues
            News::orderBy('id', 'desc')->chunk(100, function ($news) use ($file) {
                foreach ($news as $article) {
                    fputcsv($file, [
                        $article->id,
                        $article->title,
                        $article->category,
                        $article->author,
                        $article->is_published ? 'Published' : 'Draft',
                        $article->is_featured ? 'Yes' : 'No',
                        $article->published_at,
                        $article->introduction ?? '',
                        $article->reading_time . ' min',
                        $article->created_at,
                    ]);
                }
            });
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="news_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ]);
    }

    public function exportRegistrants()
    {
        // Increase memory limit for large exports
        ini_set('memory_limit', '1G');
        set_time_limit(300); // 5 minutes
        
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID', 'First Name', 'Second Name', 'Surname', 'Title', 'Email', 'Phone', 
                'Organization', 'Department', 'Country', 'Academic Qualification',
                'Course', 'Course Title', 'Registration Time', 'Mode of Payment', 
                'Total Amount', 'Invoice No', 'Registrant No', 'Accommodation',
                'Airport Pickup', 'How You Heard', 'Learning Model', 'Supervisor',
                'Supervisor Phone', 'Supervisor Email', 'Expectations', 'Additional Areas',
                'Comments'
            ]);
            
            // Process data in chunks to avoid memory issues
            Registrant::with(['course'])->orderBy('registrants_id', 'desc')->chunk(100, function ($registrants) use ($file) {
                foreach ($registrants as $registrant) {
                    fputcsv($file, [
                        $registrant->registrants_id,
                        $registrant->firstname,
                        $registrant->secondname,
                        $registrant->surname,
                        $registrant->registrant_title,
                        $registrant->personal_email,
                        $registrant->phone,
                        $registrant->organization,
                        $registrant->department,
                        $registrant->country,
                        $registrant->academic_qualification,
                        $registrant->course?->title ?? 'N/A',
                        $registrant->title_course,
                        $registrant->registered_time,
                        $registrant->mode_of_payment,
                        $registrant->total_amount,
                        $registrant->invoice_no,
                        $registrant->registrant_no,
                        $registrant->accommodation,
                        $registrant->airport_pickup,
                        $registrant->how_you_heard,
                        $registrant->learning_model,
                        $registrant->supervisor,
                        $registrant->supervisor_telephone,
                        $registrant->supervisor_email,
                        $registrant->expectations,
                        $registrant->additional_area,
                        $registrant->comment,
                    ]);
                }
            });
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="registrants_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ]);
    }

    public function exportTrainers()
    {
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Bio',
                'Specializations', 'Experience Years', 'Hourly Rate', 'Daily Rate',
                'Availability Status', 'Rating', 'Total Courses Taught',
                'Total Students Trained', 'Languages', 'City', 'Country',
                'LinkedIn Profile', 'Website', 'Is Active', 'Created At'
            ]);
            
            // Process data in chunks to avoid memory issues
            Trainer::orderBy('id', 'desc')->chunk(100, function ($trainers) use ($file) {
                foreach ($trainers as $trainer) {
                    fputcsv($file, [
                        $trainer->id,
                        $trainer->firstname,
                        $trainer->lastname,
                        $trainer->email,
                        $trainer->phone,
                        $trainer->bio,
                        $trainer->specializations_string,
                        $trainer->experience_years,
                        $trainer->hourly_rate,
                        $trainer->daily_rate,
                        $trainer->availability_status,
                        $trainer->rating,
                        $trainer->total_courses_taught,
                        $trainer->total_students_trained,
                        $trainer->languages_string,
                        $trainer->city,
                        $trainer->country,
                        $trainer->linkedin_profile,
                        $trainer->website,
                        $trainer->is_active ? 'Yes' : 'No',
                        $trainer->created_at,
                    ]);
                }
            });
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="trainers_export_' . date('Y-m-d_H-i-s') . '.csv"',
        ]);
    }
}