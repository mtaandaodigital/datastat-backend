<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Schedule;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class ScheduleGenerationService
{
    /**
     * Generate schedule date ranges based on course duration (no_of_days).
     * - 5 days: next Monday to Friday
     * - 10 days: next Monday to Friday of following week
     * - Other: fill weekdays for N days starting next Monday
     * Returns array of [start(string d/m/Y), end(string d/m/Y), start_timestamp(int)].
     */
    public function generateScheduleDates(int $noOfDays, ?Carbon $customStartDate = null): array
    {
        $start = $customStartDate ? $this->nextMonday($customStartDate) : $this->nextMonday(now());
        $periodDays = max(1, $noOfDays);

        $dates = [];
        $current = $start->copy();
        $added = 0;
        while ($added < $periodDays) {
            if ($current->isWeekday()) {
                $dates[] = $current->copy();
                $added++;
            }
            $current->addDay();
        }

        $first = Arr::first($dates) ?? $start;
        $last = Arr::last($dates) ?? $first;

        return [
            'start' => $first->format('Y-m-d'),
            'end' => $last->format('Y-m-d'),
            'start_timestamp' => $first->timestamp,
        ];
    }

    /**
     * Generate multiple consecutive schedule date ranges within a specified period.
     * Each schedule starts the Monday after the previous schedule ends.
     * Returns array of schedule ranges for the entire period.
     */
    public function generateScheduleDateRanges(int $noOfDays, Carbon $periodStart, Carbon $periodEnd, int $intervalWeeks = null): array
    {
        $ranges = [];
        $current = $this->nextMonday($periodStart);
        
        while ($current->lte($periodEnd)) {
            $scheduleRange = $this->generateScheduleDates($noOfDays, $current);
            
            // Only add if the schedule end date is within our period
            $scheduleEndDate = Carbon::createFromFormat('Y-m-d', $scheduleRange['end']);
            if ($scheduleEndDate->lte($periodEnd)) {
                $ranges[] = $scheduleRange;
                
                // Move to the Monday after this schedule ends (consecutive scheduling)
                $current = $scheduleEndDate->copy()->addDay(); // Saturday after Friday
                $current = $this->nextMonday($current); // Next Monday
            } else {
                // If this schedule would exceed the end date, stop
                break;
            }
        }
        
        return $ranges;
    }

    /**
     * Generate schedules for a single course across provided towns/prices.
     * $towns: array of [location, course_fee_usd, course_fee_ksh]
     */
    public function generateForCourse(Course $course, array $towns, array $options = []): array
    {
        $result = ['created' => 0, 'skipped' => 0, 'deleted' => 0, 'errors' => []];

        $noOfDays = (int) ($course->no_of_days ?? 5);
        
        // Check if custom date range is provided
        $periodStart = isset($options['period_start']) ? Carbon::parse($options['period_start']) : null;
        $periodEnd = isset($options['period_end']) ? Carbon::parse($options['period_end']) : null;
        
        // Generate date ranges
        if ($periodStart && $periodEnd) {
            $ranges = $this->generateScheduleDateRanges($noOfDays, $periodStart, $periodEnd);
        } else {
            // Fallback to single range (existing behavior)
            $ranges = [$this->generateScheduleDates($noOfDays)];
        }

        $mode = $options['mode'] ?? 'append'; // 'append' | 'overwrite'
        $skipSame = (bool) ($options['skip_same_range'] ?? true);

        // Overwrite mode: delete existing schedules for the specified towns
        if ($mode === 'overwrite') {
            $locations = array_values(array_filter(array_map(fn($t) => trim((string)($t['location'] ?? '')), $towns)));
            if (!empty($locations)) {
                try {
                    $deleted = Schedule::query()
                        ->where('course_id', $course->id)
                        ->whereIn('location', $locations)
                        ->delete();
                    $result['deleted'] += (int) $deleted;
                } catch (\Throwable $e) {
                    $result['errors'][] = $e->getMessage();
                }
            }
        }

        // Generate schedules for each date range
        foreach ($ranges as $range) {
            foreach ($towns as $town) {
                // Only include enabled towns if flag is present
                if (array_key_exists('enabled', $town) && !($town['enabled'])) {
                    continue;
                }

                $location = trim((string)($town['location'] ?? ''));
                if ($location === '') {
                    continue;
                }

                // Choose fee presets based on duration (prefer 10-day for >=10, else 5-day)
                if ($noOfDays >= 10) {
                    $usd = (int) ($town['price_usd_10days'] ?? $town['course_fee_usd'] ?? $town['price_usd_5days'] ?? 0);
                    $ksh = (int) ($town['price_ksh_10days'] ?? $town['course_fee_ksh'] ?? $town['price_ksh_5days'] ?? 0);
                } else {
                    $usd = (int) ($town['price_usd_5days'] ?? $town['course_fee_usd'] ?? 0);
                    $ksh = (int) ($town['price_ksh_5days'] ?? $town['course_fee_ksh'] ?? 0);
                }

                // Skip if same range exists (per location) when appending
                if ($skipSame) {
                    $exists = Schedule::query()
                        ->where('course_id', $course->id)
                        ->where('location', $location)
                        ->whereDate('start', $range['start'])
                        ->whereDate('end', $range['end'])
                        ->exists();
                    if ($exists) {
                        $result['skipped']++;
                        continue;
                    }
                }

                try {
                    Schedule::create([
                        'course_id' => $course->id,
                        'start' => $range['start'],
                        'end' => $range['end'],
                        'start_data' => (string) $range['start_timestamp'],
                        'location' => $location,
                        'course_fee_usd' => $usd,
                        'course_fee_ksh' => $ksh,
                    ]);
                    $result['created']++;
                } catch (\Throwable $e) {
                    $result['errors'][] = $e->getMessage();
                }
            }
        }

        // Optionally update course start/end to match first schedule
        if (!empty($ranges)) {
            $firstRange = $ranges[0];
            try {
                $course->update([
                    // Keep course_event.start as DATE (Y-m-d)
                    'start' => $firstRange['start'],
                    // Legacy course_event.end is string; we set date string (Y-m-d)
                    'end' => $firstRange['end'],
                    'cut_of_date' => Carbon::createFromTimestamp($firstRange['start_timestamp'])->subDays(2)->format('d/m/Y'),
                ]);
            } catch (\Throwable $e) {
                // ignore if columns not present in DB
            }
        }

        return $result;
    }

    protected function nextMonday(Carbon $from): Carbon
    {
        $date = $from->copy()->startOfDay();
        while (!$date->isMonday()) {
            $date->addDay();
        }
        return $date;
    }
}

