<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Registrant;
use App\Models\Trainer;
use App\Models\Lead;
use App\Models\News;
use App\Models\Report;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportingService
{
    public function generateReport(string $type, array $parameters = []): array
    {
        try {
            // Legacy mode: no DB persistence, generate file only
            $data = $this->getReportData($type, $parameters);
            $filePath = $this->generateReportFile($type, $data, $parameters);
            $fileSize = Storage::size($filePath);

            return [
                'success' => true,
                'file_path' => $filePath,
                'file_size' => $fileSize,
                'message' => 'Report generated successfully',
            ];

        } catch (\Exception $e) {
            if (isset($report)) {
                $report->markAsFailed($e->getMessage());
            }
            
            return [
                'success' => false,
                'message' => 'Report generation failed: ' . $e->getMessage(),
            ];
        }
    }

    protected function getReportData(string $type, array $parameters): array
    {
        $startDate = $parameters['start_date'] ?? now()->subMonths(3)->format('Y-m-d');
        $endDate = $parameters['end_date'] ?? now()->format('Y-m-d');

        switch ($type) {
            case 'course_registrations':
                return $this->getCourseRegistrationsData($startDate, $endDate, $parameters);
            
            case 'financial_summary':
                return $this->getFinancialSummaryData($startDate, $endDate, $parameters);
            
            case 'lead_conversion':
                return $this->getLeadConversionData($startDate, $endDate, $parameters);
            
            case 'registrations_by_country':
                return $this->getRegistrationsByCountryData($startDate, $endDate, $parameters);
            
            case 'registrations_summary':
                return $this->getRegistrationsSummaryData($startDate, $endDate, $parameters);
            
            case 'courses_by_category':
                return $this->getCoursesByCategoryData($startDate, $endDate, $parameters);
            
            default:
                throw new \Exception('Unknown report type: ' . $type);
        }
    }

    protected function getCourseRegistrationsData(string $startDate, string $endDate, array $parameters): array
    {
        $query = Registrant::with(['schedule.course'])
            ->join('course_schedule', 'registrants.schedule_id', '=', 'course_schedule.schedule_id')
            ->whereBetween('registrants.registered_time', [$startDate, $endDate]);

        if (isset($parameters['course_id'])) {
            $query->where('course_schedule.id', $parameters['course_id']);
        }

        $registrants = $query->select('registrants.*')->orderBy('registrants.registered_time', 'desc')->get();

        return [
            'title' => 'Course Registrations Report',
            'period' => $startDate . ' to ' . $endDate,
            'total_registrations' => $registrants->count(),
            'registrants' => $registrants->map(function ($registrant) {
                $schedule = $registrant->schedule;
                return [
                    'id' => $registrant->registrants_id,
                    'name' => $registrant->full_name,
                    'email' => $registrant->personal_email ?: $registrant->official_email,
                    'phone' => $registrant->phone,
                    'course' => $schedule->course->title ?? 'N/A',
                    'location' => $schedule->location ?? 'N/A',
                    'organization' => $registrant->organization,
                    'country' => $registrant->country,
                    'registration_date' => $registrant->registered_time,
                    'course_start' => $schedule->start,
                    'course_end' => $schedule->end,
                    'payment_status' => $registrant->payment_status ?? 'Unknown',
                ];
            })->toArray(),
        ];
    }

    protected function getFinancialSummaryData(string $startDate, string $endDate, array $parameters): array
    {
        $registrants = Registrant::with(['schedule.course'])
            ->join('course_schedule', 'registrants.schedule_id', '=', 'course_schedule.schedule_id')
            ->whereBetween('registrants.registered_time', [$startDate, $endDate])
            ->select('registrants.*')
            ->get();

        // Group by course
        $courseRevenue = $registrants->groupBy(function ($r) {
            return $r->schedule->course->id ?? 'unknown';
        })->map(function ($group) {
            $course = $group->first()->schedule->course;
            return [
                'course' => $course->title ?? 'Unknown',
                'registrations' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
                'avg_amount' => $group->avg('total_amount'),
            ];
        })->values()->toArray();

        // Group by payment status
        $paymentSummary = $registrants->groupBy('payment_status')->map(function ($group) {
            $status = $group->first()->payment_status;
            return [
                'status' => $status ?? 'Unknown',
                'count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
            ];
        })->values()->toArray();

        // Group by mode of payment
        $paymentMethods = $registrants->groupBy('mode_of_payment')->map(function ($group) {
            $method = $group->first()->mode_of_payment;
            return [
                'method' => $method ?? 'Unknown',
                'count' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
            ];
        })->values()->toArray();

        return [
            'title' => 'Financial Summary Report',
            'period' => $startDate . ' to ' . $endDate,
            'total_registrations' => $registrants->count(),
            'total_amount' => $registrants->sum('total_amount'),
            'average_amount_per_registrant' => $registrants->avg('total_amount'),
            'course_revenue_breakdown' => $courseRevenue,
            'payment_status_summary' => $paymentSummary,
            'payment_methods_summary' => $paymentMethods,
        ];
    }

    protected function getTrainerPerformanceData(string $startDate, string $endDate, array $parameters): array
    {
        $trainers = Trainer::with(['courses' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('start_date', [$startDate, $endDate]);
        }])->get();

        return [
            'title' => 'Trainer Performance Report',
            'period' => $startDate . ' to ' . $endDate,
            'trainers' => $trainers->map(function ($trainer) {
                $courses = $trainer->courses;
                $totalStudents = $courses->sum(function ($course) {
                    return $course->registrants()->count();
                });

                return [
                    'id' => $trainer->id,
                    'name' => $trainer->full_name,
                    'email' => $trainer->email,
                    'specializations' => implode(', ', $trainer->specializations ?? []),
                    'courses_taught' => $courses->count(),
                    'students_trained' => $totalStudents,
                    'rating' => $trainer->rating,
                    'daily_rate' => $trainer->daily_rate,
                    'availability_status' => $trainer->availability_status,
                    'courses' => $courses->map(function ($course) {
                        return [
                            'title' => $course->title,
                            'start_date' => $course->start_date,
                            'registrations' => $course->registrants()->count(),
                            'completion_rate' => $course->completion_rate,
                        ];
                    })->toArray(),
                ];
            })->toArray(),
        ];
    }

    protected function getLeadConversionData(string $startDate, string $endDate, array $parameters): array
    {
        $leads = Lead::whereBetween('created_at', [$startDate, $endDate])->get();

        $conversionsBySource = $leads->groupBy('source')->map(function ($group, $source) {
            $total = $group->count();
            $converted = $group->where('status', 'Converted')->count();
            
            return [
                'source' => $source,
                'total_leads' => $total,
                'converted_leads' => $converted,
                'conversion_rate' => $total > 0 ? round(($converted / $total) * 100, 2) : 0,
                'potential_value' => $group->sum('potential_value'),
            ];
        })->values()->toArray();

        return [
            'title' => 'Lead Conversion Report',
            'period' => $startDate . ' to ' . $endDate,
            'total_leads' => $leads->count(),
            'converted_leads' => $leads->where('status', 'Converted')->count(),
            'overall_conversion_rate' => $leads->count() > 0 ? 
                round(($leads->where('status', 'Converted')->count() / $leads->count()) * 100, 2) : 0,
            'total_potential_value' => $leads->sum('potential_value'),
            'conversion_by_source' => $conversionsBySource,
            'leads_by_status' => $leads->groupBy('status')->map(function ($group, $status) {
                return [
                    'status' => $status,
                    'count' => $group->count(),
                    'percentage' => round(($group->count() / $group->count()) * 100, 2),
                ];
            })->values()->toArray(),
        ];
    }

    protected function getAttendanceReportData(string $startDate, string $endDate, array $parameters): array
    {
        $query = Registrant::with('course')
            ->whereHas('course', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate]);
            });

        if (isset($parameters['course_id'])) {
            $query->where('course_id', $parameters['course_id']);
        }

        $registrants = $query->get();

        return [
            'title' => 'Attendance Report',
            'period' => $startDate . ' to ' . $endDate,
            'total_registrants' => $registrants->count(),
            'present' => $registrants->where('attendance_status', 'Present')->count(),
            'absent' => $registrants->where('attendance_status', 'Absent')->count(),
            'partial' => $registrants->where('attendance_status', 'Partial')->count(),
            'attendance_rate' => $registrants->count() > 0 ? 
                round(($registrants->where('attendance_status', 'Present')->count() / $registrants->count()) * 100, 2) : 0,
            'course_attendance' => $registrants->groupBy('course_id')->map(function ($group) {
                $course = $group->first()->course;
                return [
                    'course' => $course->title ?? 'Unknown',
                    'total_registrants' => $group->count(),
                    'present' => $group->where('attendance_status', 'Present')->count(),
                    'attendance_rate' => $group->count() > 0 ? 
                        round(($group->where('attendance_status', 'Present')->count() / $group->count()) * 100, 2) : 0,
                ];
            })->values()->toArray(),
        ];
    }

    protected function getCompletionRatesData(string $startDate, string $endDate, array $parameters): array
    {
        $query = Registrant::with('course')
            ->whereHas('course', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('end_date', [$startDate, $endDate]);
            });

        $registrants = $query->get();

        return [
            'title' => 'Course Completion Rates Report',
            'period' => $startDate . ' to ' . $endDate,
            'total_registrants' => $registrants->count(),
            'completed' => $registrants->where('completion_status', 'Completed')->count(),
            'in_progress' => $registrants->where('completion_status', 'In Progress')->count(),
            'dropped_out' => $registrants->where('completion_status', 'Dropped Out')->count(),
            'overall_completion_rate' => $registrants->count() > 0 ? 
                round(($registrants->where('completion_status', 'Completed')->count() / $registrants->count()) * 100, 2) : 0,
            'course_completion' => $registrants->groupBy('course_id')->map(function ($group) {
                $course = $group->first()->course;
                return [
                    'course' => $course->title ?? 'Unknown',
                    'total_registrants' => $group->count(),
                    'completed' => $group->where('completion_status', 'Completed')->count(),
                    'completion_rate' => $group->count() > 0 ? 
                        round(($group->where('completion_status', 'Completed')->count() / $group->count()) * 100, 2) : 0,
                    'certificates_issued' => $group->where('certificate_issued', true)->count(),
                ];
            })->values()->toArray(),
        ];
    }

    protected function getRevenueAnalysisData(string $startDate, string $endDate, array $parameters): array
    {
        $registrants = Registrant::with('course')
            ->where('payment_status', 'Paid')
            ->whereBetween('registration_date', [$startDate, $endDate])
            ->get();

        // Monthly revenue breakdown
        $monthlyRevenue = $registrants->groupBy(function ($registrant) {
            return $registrant->registration_date->format('Y-m');
        })->map(function ($group, $month) {
            return [
                'month' => $month,
                'revenue' => $group->sum('final_amount'),
                'registrations' => $group->count(),
            ];
        })->values()->toArray();

        return [
            'title' => 'Revenue Analysis Report',
            'period' => $startDate . ' to ' . $endDate,
            'total_revenue' => $registrants->sum('final_amount'),
            'total_registrations' => $registrants->count(),
            'average_revenue_per_registration' => $registrants->avg('final_amount'),
            'monthly_revenue' => $monthlyRevenue,
            'top_revenue_courses' => $registrants->groupBy('course_id')
                ->map(function ($group) {
                    $course = $group->first()->course;
                    return [
                        'course' => $course->title ?? 'Unknown',
                        'revenue' => $group->sum('final_amount'),
                        'registrations' => $group->count(),
                    ];
                })
                ->sortByDesc('revenue')
                ->take(10)
                ->values()
                ->toArray(),
        ];
    }

    protected function getStudentFeedbackData(string $startDate, string $endDate, array $parameters): array
    {
        $registrants = Registrant::with('course')
            ->whereNotNull('rating')
            ->where('feedback_submitted', true)
            ->whereHas('course', function ($q) use ($startDate, $endDate) {
                $q->whereBetween('end_date', [$startDate, $endDate]);
            })
            ->get();

        return [
            'title' => 'Student Feedback Report',
            'period' => $startDate . ' to ' . $endDate,
            'total_feedback_submissions' => $registrants->count(),
            'average_rating' => $registrants->avg('rating'),
            'rating_distribution' => [
                '5_stars' => $registrants->where('rating', 5)->count(),
                '4_stars' => $registrants->where('rating', 4)->count(),
                '3_stars' => $registrants->where('rating', 3)->count(),
                '2_stars' => $registrants->where('rating', 2)->count(),
                '1_star' => $registrants->where('rating', 1)->count(),
            ],
            'course_ratings' => $registrants->groupBy('course_id')->map(function ($group) {
                $course = $group->first()->course;
                return [
                    'course' => $course->title ?? 'Unknown',
                    'average_rating' => $group->avg('rating'),
                    'total_feedback' => $group->count(),
                ];
            })->values()->toArray(),
        ];
    }

    protected function getCoursePopularityData(string $startDate, string $endDate, array $parameters): array
    {
        $courses = Course::withCount(['registrants' => function ($query) use ($startDate, $endDate) {
            $query->whereBetween('registration_date', [$startDate, $endDate]);
        }])->get();

        return [
            'title' => 'Course Popularity Report',
            'period' => $startDate . ' to ' . $endDate,
            'courses' => $courses->sortByDesc('registrants_count')->map(function ($course) {
                return [
                    'course' => $course->title,
                    'category' => $course->category,
                    'registrations' => $course->registrants_count,
                    'revenue' => $course->registrants()->where('payment_status', 'Paid')->sum('final_amount'),
                    'completion_rate' => $course->completion_rate,
                    'start_date' => $course->start_date,
                    'location' => $course->location,
                ];
            })->values()->toArray(),
        ];
    }

    protected function getPaymentStatusData(string $startDate, string $endDate, array $parameters): array
    {
        $registrants = Registrant::with('course')
            ->whereBetween('registration_date', [$startDate, $endDate])
            ->get();

        return [
            'title' => 'Payment Status Report',
            'period' => $startDate . ' to ' . $endDate,
            'payment_summary' => [
                'paid' => $registrants->where('payment_status', 'Paid')->count(),
                'pending' => $registrants->where('payment_status', 'Pending')->count(),
                'failed' => $registrants->where('payment_status', 'Failed')->count(),
                'refunded' => $registrants->where('payment_status', 'Refunded')->count(),
            ],
            'revenue_summary' => [
                'collected' => $registrants->where('payment_status', 'Paid')->sum('final_amount'),
                'pending' => $registrants->where('payment_status', 'Pending')->sum('final_amount'),
                'refunded' => $registrants->where('payment_status', 'Refunded')->sum('final_amount'),
            ],
            'overdue_payments' => $registrants->where('payment_status', 'Pending')
                ->filter(function ($registrant) {
                    return $registrant->course && $registrant->course->start_date < now();
                })
                ->map(function ($registrant) {
                    return [
                        'name' => $registrant->full_name,
                        'email' => $registrant->email,
                        'course' => $registrant->course->title ?? 'Unknown',
                        'amount_due' => $registrant->final_amount,
                        'days_overdue' => now()->diffInDays($registrant->course->start_date),
                    ];
                })->values()->toArray(),
        ];
    }

    protected function generateReportFile(string $type, array $data, array $parameters): string
    {
        $format = $parameters['format'] ?? 'csv';
        $filename = $type . '_' . date('Y-m-d_H-i-s') . '.' . $format;
        $filePath = 'reports/' . $filename;

        switch ($format) {
            case 'csv':
                $this->generateCSVFile($filePath, $data);
                break;
            case 'json':
                $this->generateJSONFile($filePath, $data);
                break;
            case 'pdf':
                $this->generatePDFFile($filePath, $data);
                break;
            default:
                throw new \Exception('Unsupported format: ' . $format);
        }

        return $filePath;
    }

    protected function generateCSVFile(string $filePath, array $data): void
    {
        $content = '';
        
        // Add header information
        $content .= "Report: " . $data['title'] . "\n";
        $content .= "Period: " . $data['period'] . "\n";
        $content .= "Generated: " . now()->format('Y-m-d H:i:s') . "\n\n";

        // Add summary data
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                // This is tabular data
                $content .= strtoupper(str_replace('_', ' ', $key)) . "\n";
                
                if (!empty($value)) {
                    // Add headers
                    $headers = array_keys($value[0]);
                    $content .= implode(',', $headers) . "\n";
                    
                    // Add data rows
                    foreach ($value as $row) {
                        $content .= implode(',', array_map(function($cell) {
                            return '"' . str_replace('"', '""', $cell) . '"';
                        }, $row)) . "\n";
                    }
                }
                $content .= "\n";
            } elseif (!is_array($value)) {
                $content .= ucfirst(str_replace('_', ' ', $key)) . ": " . $value . "\n";
            }
        }

        Storage::put($filePath, $content);
    }

    protected function generateJSONFile(string $filePath, array $data): void
    {
        Storage::put($filePath, json_encode($data, JSON_PRETTY_PRINT));
    }

    protected function generatePDFFile(string $filePath, array $data): void
    {
        $html = $this->generatePDFHTML($data);
        
        // Use a simple HTML to PDF conversion - save as HTML with PDF styling
        // For proper PDF, you would need dompdf or similar library
        // For now, we'll save as HTML which can be printed to PDF
        $pdfPath = str_replace('.pdf', '.html', $filePath);
        Storage::put($pdfPath, $html);
        
        // Also save as PDF-ready HTML
        $pdfContent = $this->convertHTMLToPDF($html);
        Storage::put($filePath, $pdfContent);
    }

    protected function generatePDFHTML(array $data): string
    {
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>' . ($data['title'] ?? 'Report') . '</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .header { border-bottom: 3px solid #007bff; padding-bottom: 15px; margin-bottom: 20px; }
        h1 { color: #007bff; font-size: 28px; margin-bottom: 5px; }
        .period { color: #666; font-size: 14px; }
        .generated-date { text-align: right; color: #999; font-size: 12px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        th { background-color: #007bff; color: white; padding: 12px; text-align: left; }
        td { padding: 10px 12px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .summary { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .summary-box { background: #f0f4ff; padding: 15px; border-left: 4px solid #007bff; }
        .summary-label { font-size: 12px; color: #666; text-transform: uppercase; }
        .summary-value { font-size: 24px; font-weight: bold; color: #007bff; margin-top: 5px; }
        .section-title { background-color: #e8f0ff; padding: 10px 12px; font-weight: bold; color: #007bff; margin-top: 20px; margin-bottom: 10px; }
        @media print { 
            body { padding: 0; } 
            .container { max-width: 100%; }
            table { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>' . htmlspecialchars($data['title'] ?? 'Report') . '</h1>
            <div class="period">' . htmlspecialchars($data['period'] ?? '') . '</div>
        </div>
        <div class="generated-date">Generated: ' . now()->format('Y-m-d H:i:s') . '</div>
';

        // Add summary info
        foreach ($data as $key => $value) {
            if (!is_array($value) && !in_array($key, ['title', 'period', 'data'])) {
                $html .= '<div class="summary"><div class="summary-box">
                    <div class="summary-label">' . htmlspecialchars(str_replace('_', ' ', $key)) . '</div>
                    <div class="summary-value">' . htmlspecialchars($value) . '</div>
                </div></div>';
            }
        }

        // Add tabular data
        if (isset($data['data']) && is_array($data['data'])) {
            $html .= '<div class="section-title">Details</div>';
            $html .= '<table><thead><tr>';
            
            if (!empty($data['data'])) {
                $firstRow = is_array($data['data'][0]) ? $data['data'][0] : [];
                foreach (array_keys($firstRow) as $header) {
                    $html .= '<th>' . htmlspecialchars(str_replace('_', ' ', $header)) . '</th>';
                }
            }
            
            $html .= '</tr></thead><tbody>';
            
            foreach ($data['data'] as $row) {
                if (is_array($row)) {
                    $html .= '<tr>';
                    foreach ($row as $cell) {
                        $cellValue = is_array($cell) ? json_encode($cell) : $cell;
                        $html .= '<td>' . htmlspecialchars($cellValue) . '</td>';
                    }
                    $html .= '</tr>';
                }
            }
            
            $html .= '</tbody></table>';
        }

        // Add summary section if exists
        if (isset($data['summary']) && is_array($data['summary'])) {
            $html .= '<div class="section-title">Summary</div>';
            foreach ($data['summary'] as $section => $items) {
                if (is_array($items) && !empty($items)) {
                    $html .= '<h3 style="color: #007bff; margin: 15px 0 10px 0;">' . htmlspecialchars(str_replace('_', ' ', $section)) . '</h3>';
                    if (isset($items[0]) && is_array($items[0])) {
                        $html .= '<table><thead><tr>';
                        foreach (array_keys($items[0]) as $header) {
                            $html .= '<th>' . htmlspecialchars(str_replace('_', ' ', $header)) . '</th>';
                        }
                        $html .= '</tr></thead><tbody>';
                        foreach ($items as $row) {
                            $html .= '<tr>';
                            foreach ($row as $cell) {
                                $cellValue = is_array($cell) ? json_encode($cell) : $cell;
                                $html .= '<td>' . htmlspecialchars($cellValue) . '</td>';
                            }
                            $html .= '</tr>';
                        }
                        $html .= '</tbody></table>';
                    }
                }
            }
        }

        $html .= '
    </div>
</body>
</html>';

        return $html;
    }

    protected function convertHTMLToPDF(string $html): string
    {
        // Since we don't have dompdf installed, we'll return the HTML
        // In production, you would use: 
        // $dompdf = new Dompdf();
        // $dompdf->loadHtml($html);
        // $dompdf->render();
        // return $dompdf->output();
        
        // For now, return the HTML which can be printed to PDF from browser
        return $html;
    }

    protected function getReportName(string $type): string
    {
        return Report::getTypes()[$type] ?? ucfirst(str_replace('_', ' ', $type));
    }

    protected function getReportDescription(string $type): string
    {
        $descriptions = [
            'course_registrations' => 'Detailed report of course registrations with participant information',
            'financial_summary' => 'Financial overview including revenue, payments, and course profitability',
            'lead_conversion' => 'Lead conversion analysis by source and status',
            'registrations_by_country' => 'Registrations grouped by country',
            'registrations_summary' => 'Summary statistics of all registrations',
            'courses_by_category' => 'Courses organized by category with registration counts',
        ];

        return $descriptions[$type] ?? 'Generated report for ' . $type;
    }

    protected function getRegistrationsByCountryData(string $startDate, string $endDate, array $parameters): array
    {
        $registrants = Registrant::with(['schedule.course'])
            ->join('course_schedule', 'registrants.schedule_id', '=', 'course_schedule.schedule_id')
            ->whereBetween('registrants.registered_time', [$startDate, $endDate])
            ->select('registrants.*')
            ->get();

        $byCountry = $registrants->groupBy('country')->map(function ($group) {
            return [
                'country' => $group->first()->country ?? 'Unknown',
                'total_registrations' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
                'organizations' => $group->pluck('organization')->unique()->count(),
                'payment_statuses' => $group->groupBy('payment_status')->map(function ($g) {
                    return $g->count();
                })->toArray(),
            ];
        })->sortByDesc('total_registrations')->values()->toArray();

        return [
            'title' => 'Registrations by Country',
            'period' => $startDate . ' to ' . $endDate,
            'total_registrations' => $registrants->count(),
            'total_countries' => $registrants->pluck('country')->unique()->count(),
            'data' => $byCountry,
        ];
    }

    protected function getRegistrationsByOrganizationData(string $startDate, string $endDate, array $parameters): array
    {
        $registrants = Registrant::with(['schedule.course'])
            ->join('course_schedule', 'registrants.schedule_id', '=', 'course_schedule.schedule_id')
            ->whereBetween('registrants.registered_time', [$startDate, $endDate])
            ->select('registrants.*')
            ->get();

        $byOrganization = $registrants->groupBy('organization')->map(function ($group) {
            return [
                'organization' => $group->first()->organization ?? 'Unknown',
                'total_registrations' => $group->count(),
                'total_amount' => $group->sum('total_amount'),
                'countries' => $group->pluck('country')->unique()->count(),
                'courses' => $group->pluck('schedule.course.title')->unique()->count(),
                'payment_statuses' => $group->groupBy('payment_status')->map(function ($g) {
                    return $g->count();
                })->toArray(),
            ];
        })->sortByDesc('total_registrations')->values()->toArray();

        return [
            'title' => 'Registrations by Organization',
            'period' => $startDate . ' to ' . $endDate,
            'total_registrations' => $registrants->count(),
            'total_organizations' => $registrants->pluck('organization')->unique()->count(),
            'data' => $byOrganization,
        ];
    }

    protected function getRegistrationsSummaryData(string $startDate, string $endDate, array $parameters): array
    {
        $registrants = Registrant::with(['schedule.course'])
            ->join('course_schedule', 'registrants.schedule_id', '=', 'course_schedule.schedule_id')
            ->whereBetween('registrants.registered_time', [$startDate, $endDate])
            ->select('registrants.*')
            ->get();

        $summary = [
            'total_registrations' => $registrants->count(),
            'total_amount' => $registrants->sum('total_amount'),
            'average_per_registrant' => $registrants->avg('total_amount'),
            'by_payment_status' => $registrants->groupBy('payment_status')->map(function ($g) {
                return [
                    'status' => $g->first()->payment_status ?? 'Unknown',
                    'count' => $g->count(),
                    'total_amount' => $g->sum('total_amount'),
                ];
            })->values()->toArray(),
            'by_country' => $registrants->groupBy('country')->map(function ($g) {
                return [
                    'country' => $g->first()->country ?? 'Unknown',
                    'count' => $g->count(),
                ];
            })->values()->sortByDesc('count')->toArray(),
            'by_course' => $registrants->groupBy(function ($r) {
                return $r->schedule->course->id ?? 'unknown';
            })->map(function ($g) {
                return [
                    'course' => $g->first()->schedule->course->title ?? 'Unknown',
                    'count' => $g->count(),
                    'total_amount' => $g->sum('total_amount'),
                ];
            })->values()->sortByDesc('count')->toArray(),
        ];

        return [
            'title' => 'Registrations Summary',
            'period' => $startDate . ' to ' . $endDate,
            'summary' => $summary,
        ];
    }

    protected function getCoursesByCategoryData(string $startDate, string $endDate, array $parameters): array
    {
        $query = Course::query();

        // Filter by specific category if provided
        if (isset($parameters['category']) && $parameters['category']) {
            $query->where('id', $parameters['category']);
        }

        $courses = $query->get();

        $byCategory = $courses->groupBy('category')->map(function ($group) use ($startDate, $endDate) {
            $courseIds = $group->pluck('id')->toArray();
            $registrants = Registrant::with('schedule')
                ->whereIn('course_id', $courseIds)
                ->whereBetween('registered_time', [$startDate, $endDate])
                ->get();

            return [
                'category' => $group->first()->category ?? 'Unknown',
                'total_courses' => $group->count(),
                'total_registrations' => $registrants->count(),
                'total_amount' => $registrants->sum('total_amount'),
                'courses' => $group->map(function ($course) use ($startDate, $endDate) {
                    $regCount = Registrant::where('course_id', $course->id)
                        ->whereBetween('registered_time', [$startDate, $endDate])
                        ->count();
                    return [
                        'title' => $course->title,
                        'registrations' => $regCount,
                    ];
                })->toArray(),
            ];
        })->values()->sortByDesc('total_registrations')->toArray();

        return [
            'title' => 'Courses by Category',
            'period' => $startDate . ' to ' . $endDate,
            'total_courses' => $courses->count(),
            'total_categories' => $courses->pluck('category')->unique()->count(),
            'data' => $byCategory,
        ];
}
}