<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class CsvUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB max
            'has_header' => 'boolean',
            'schedule_end_date' => 'nullable|date',
        ]);

        $file = $request->file('csv_file');
        $hasHeader = $request->boolean('has_header', true);
        $scheduleEndDate = $request->input('schedule_end_date');

        if (!$file || !$file->isValid()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid file uploaded'
            ], 400);
        }

        $fullPath = $file->getRealPath();

        $headers = [];
        $total = 0; $success = 0; $failed = 0; $errors = [];
        $handle = null; $fatalError = null;

        try {
            $handle = fopen($fullPath, 'r');
            if (!$handle) {
                throw new \RuntimeException('Could not open file');
            }

            // Detect delimiter from the first line
            $firstLine = fgets($handle) ?: '';
            $delimiter = $this->detectDelimiter($firstLine);
            // Rewind to start
            rewind($handle);

            if ($hasHeader) {
                $headers = fgetcsv($handle, 0, $delimiter) ?: [];
                // Normalize headers: strip BOM, trim, lowercase
                $headers = array_map(function ($h) {
                    return Str::of($this->stripBom((string) $h))->trim()->lower()->toString();
                }, $headers);
            }

            $perCourse = [];
            while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
                // Skip completely empty rows
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) {
                    continue;
                }

                $total++;
                try {
                    if (!empty($headers)) {
                        // Map header-based row
                        $data = [];
                        foreach ($headers as $i => $key) {
                            if ($key === '') { continue; }
                            $data[$key] = $row[$i] ?? '';
                        }

                        // Alias common header variants to expected keys
                        if (empty($data['title'] ?? '')) {
                            foreach (['course_title', 'course name', 'course', 'name'] as $alt) {
                                if (!empty($data[$alt] ?? '')) { $data['title'] = $data[$alt]; break; }
                            }
                        }
                        if (empty($data['no_of_days'] ?? '') && !empty($data['days'] ?? '')) {
                            $data['no_of_days'] = $data['days'];
                        }
                        if (empty($data['fee_kes'] ?? '') && !empty($data['kes'] ?? '')) {
                            $data['fee_kes'] = $data['kes'];
                        }
                        if (empty($data['fee_usd'] ?? '') && !empty($data['usd'] ?? '')) {
                            $data['fee_usd'] = $data['usd'];
                        }
                        if (empty($data['fee_euro'] ?? '') && !empty($data['euro'] ?? '')) {
                            $data['fee_euro'] = $data['euro'];
                        }
                        if (empty($data['fee_pound'] ?? '') && !empty($data['pound'] ?? '')) {
                            $data['fee_pound'] = $data['pound'];
                        }
                        if (empty($data['location'] ?? '') && !empty($data['town'] ?? '')) {
                            $data['location'] = $data['town'];
                        }
                    } else {
                        // Positional mapping based on legacy format
                        $data = [
                            'title' => $row[0] ?? '',
                            'category' => $row[1] ?? 'General',
                            'no_of_days' => $row[2] ?? 5,
                            'introduction' => $row[3] ?? '',
                            'body' => $row[4] ?? '',
                            'location' => $row[5] ?? '',
                            'fee_kes' => $row[6] ?? 0,
                            'fee_usd' => $row[7] ?? 0,
                            'fee_euro' => $row[8] ?? 0,
                            'fee_pound' => $row[9] ?? 0,
                        ];
                    }

                    $created = $this->createCourseAndSchedules($data, $scheduleEndDate);
                    if ($created && ($created['id'] ?? null)) { 
                        $success++; 
                        $perCourse[] = [
                            'title' => $created['title'] ?? ($data['title'] ?? 'Unknown'),
                            'schedules' => (int) ($created['schedules_created'] ?? 0),
                        ];
                    } else { 
                        $failed++; 
                    }
                } catch (\Throwable $e) {
                    $failed++;
                    $errors[] = $e->getMessage();
                }
            }
        } catch (\Throwable $e) {
            $fatalError = $e->getMessage();
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if ($fatalError) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $fatalError
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => "Imported {$success}/{$total} courses",
            'details' => [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'errors' => array_slice($errors, 0, 5),
                'per_course' => $perCourse,
                'total_schedules_created' => array_sum(array_map(fn($c) => (int) $c['schedules'], $perCourse)),
            ]
        ]);
    }

    protected function detectDelimiter(string $line): string
    {
        $line = $this->stripBom($line);
        $candidates = [',' => substr_count($line, ','), ';' => substr_count($line, ';'), "\t" => substr_count($line, "\t"), '|' => substr_count($line, '|')];
        arsort($candidates);
        $best = array_key_first($candidates);
        return $candidates[$best] > 0 ? $best : ',';
    }

    protected function stripBom(string $text): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $text) ?? $text;
    }

    protected function createCourseAndSchedules(array $data, ?string $scheduleEndDate = null): ?array
    {
        $title = trim((string)($data['title'] ?? ''));
        if ($title === '') return null;

        $category = (string) ($data['category'] ?? 'General');
        $noOfDays = (int) ($data['no_of_days'] ?? 5);
        $intro = (string) ($data['introduction'] ?? '');
        $body = (string) ($data['body'] ?? '');
        $location = trim((string) ($data['location'] ?? ''));
        if ($location === '') $location = 'Nairobi'; // Default to Nairobi if CSV location is blank

        $fee_kes = (int) ($data['fee_kes'] ?? 0);
        $fee_usd = (int) ($data['fee_usd'] ?? 0);
        $fee_euro = (int) ($data['fee_euro'] ?? 0);
        $fee_pound = (int) ($data['fee_pound'] ?? 0);

        // Ensure image_path is always a non-empty filename based on title
        $defaultImage = Str::slug($title) . '-by-datastat-training-institute.jpg';

        $course = \App\Models\Course::create([
            'title' => $title,
            'category' => $category,
            'start_date' => (int) now()->timestamp, // UNIX timestamp
            'end' => '2027-01-01', // Fixed field name and default date
            'cut_of_date' => '', // VARCHAR field
            'no_of_days' => $noOfDays,
            'introduction' => $intro,
            'body' => $body,
            'course_status' => ($data['course_status'] ?? 'General'),
            'location' => $location,
            'software' => $data['software'] ?? '',
            'fee_kes' => $fee_kes,
            'fee_usd' => $fee_usd,
            'fee_euro' => $fee_euro,
            'fee_pound' => $fee_pound,
            'image_path' => !empty($data['image_path'] ?? '') ? $data['image_path'] : $defaultImage,
            'submitted_time' => now()->format('Y-m-d H:i:s'),
            'schedule_status' => 1,
        ]);

        // Auto-generate schedules using same logic as generator
        $service = new \App\Services\ScheduleGenerationService();
        $schedulesCreated = 0;
        
        // If end date is provided, generate multiple consecutive schedules
        if ($scheduleEndDate) {
            $periodStart = now(); // Start from today (will find next Monday)
            $periodEnd = \Carbon\Carbon::parse($scheduleEndDate);
            
            // Use the schedule generation service with period dates
            $towns = [
                [
                    'location' => $location,
                    'course_fee_usd' => $fee_usd,
                    'course_fee_ksh' => $fee_kes,
                ]
            ];
            
            $options = [
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'mode' => 'append',
                'skip_same_range' => true,
            ];
            
            $result = $service->generateForCourse($course, $towns, $options);
            $schedulesCreated = (int) ($result['created'] ?? 0);
            // No fallback: if no schedules are created within the period, do not create a single schedule.
        } else {
            // Single schedule only (original behavior)
            $range = $service->generateScheduleDates($noOfDays);
            
            \App\Models\Schedule::create([
                'course_id' => $course->id,
                'start' => \Carbon\Carbon::parse($range['start'])->format('Y-m-d'),
                'end' => \Carbon\Carbon::parse($range['end'])->format('Y-m-d'),
                'start_data' => (string) $range['start_timestamp'],
                'location' => $location,
                'course_fee_usd' => $fee_usd,
                'course_fee_ksh' => $fee_kes,
            ]);
            $schedulesCreated = 1;
            
            // Update course dates
            $course->update([
                'start' => $range['start'], 
                'end' => $range['end'], // VARCHAR field
                'cut_of_date' => now()->subDays(2)->format('d/m/Y'), // VARCHAR field
            ]);
        }

        return [
            'id' => (int) $course->id,
            'title' => $title,
            'schedules_created' => $schedulesCreated,
        ];
    }
}