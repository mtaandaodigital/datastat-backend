<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Models\Registrant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Calendar extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static string $view = 'filament.pages.calendar';

    protected static ?string $navigationGroup = 'Management';

    protected static ?int $navigationSort = 10;

    public ?string $currentMonth = null;

    public ?int $selectedWeekIndex = null;

    public bool $showDatePicker = false;

    public ?string $selectedYear = null;

    public $selectedRegistrant = null;

    public $showModal = false;

    public $customMessage = '';

    public static function canAccess(): bool
    {
        // Assuming super admin has accesslevel 1
        return auth()->check() && auth()->user()->accesslevel == 1;
    }

    public function mount(): void
    {
        $this->currentMonth = now()->format('Y-m');
        $this->selectedYear = now()->format('Y');
    }

    public function goToPreviousMonth(): void
    {
        $this->currentMonth = Carbon::createFromFormat('Y-m', $this->currentMonth)->subMonth()->format('Y-m');
        $this->selectedWeekIndex = null;
    }

    public function goToNextMonth(): void
    {
        $this->currentMonth = Carbon::createFromFormat('Y-m', $this->currentMonth)->addMonth()->format('Y-m');
        $this->selectedWeekIndex = null;
    }

    public function openModal($registrantId): void
    {
        $this->selectedRegistrant = Registrant::with(['schedule.course'])->find($registrantId);
        $this->showModal = true;
        $this->customMessage = '';
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->selectedRegistrant = null;
        $this->customMessage = '';
    }

    public function selectWeek(?int $weekIndex): void
    {
        $this->selectedWeekIndex = $this->selectedWeekIndex === $weekIndex ? null : $weekIndex;
    }

    public function toggleDatePicker(): void
    {
        $this->showDatePicker = !$this->showDatePicker;
    }

    public function selectMonth(string $yearMonth): void
    {
        $this->currentMonth = $yearMonth;
        $this->selectedWeekIndex = null;
        $this->showDatePicker = false;
    }

    public function sendReminder(): void
    {
        if ($this->selectedRegistrant) {
            $this->selectedRegistrant->sendReminder($this->customMessage ?: null);
            $this->closeModal();
            // Add notification or flash message
        }
    }

    protected function getWeeksInMonth(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->currentMonth);
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        $weeks = [];
        $current = $startOfMonth->copy()->startOfWeek(Carbon::MONDAY);

        while ($current->lte($endOfMonth)) {
            $weekStart = $current->copy();
            $weekEnd = $current->copy()->endOfWeek(Carbon::SUNDAY);
            $weeks[] = [
                'start' => $weekStart,
                'end' => $weekEnd,
                'label' => $weekStart->format('M d') . ' - ' . $weekEnd->format('M d'),
            ];
            $current->addWeek();
        }

        return $weeks;
    }

    protected function getRegistrationsForWeek(int $weekIndex): array
    {
        $weeks = $this->getWeeksInMonth();
        if (!isset($weeks[$weekIndex])) {
            return [];
        }

        $week = $weeks[$weekIndex];
        $weekStart = $week['start'];
        $weekEnd = $week['end'];

        // Get the current month boundaries
        $month = Carbon::createFromFormat('Y-m', $this->currentMonth);
        $monthStart = $month->copy()->startOfMonth();
        $monthEnd = $month->copy()->endOfMonth();

        return Registrant::query()
            ->with(['schedule.course'])
            ->join('course_schedule', 'registrants.schedule_id', '=', 'course_schedule.schedule_id')
            ->where(function (Builder $query) use ($weekStart, $weekEnd, $monthStart, $monthEnd) {
                $query->whereDate('course_schedule.start', '<=', $weekEnd)
                    ->whereDate('course_schedule.end', '>=', $weekStart)
                    ->whereDate('course_schedule.start', '>=', $monthStart)
                    ->whereDate('course_schedule.start', '<=', $monthEnd);
            })
            ->orderBy('course_schedule.start')
            ->select('registrants.*')
            ->get()
            ->map(function (Registrant $registrant) {
                $schedule = $registrant->schedule;
                if (!$schedule) {
                    return null;
                }

                $startDate = Carbon::parse($schedule->start);
                $endDate = Carbon::parse($schedule->end);

                return [
                    'id' => $registrant->registrants_id,
                    'name' => $registrant->full_name,
                    'course' => $schedule->course?->title ?? $registrant->title_course,
                    'location' => $schedule->location,
                    'start' => $startDate,
                    'end' => $endDate,
                    'registration_date' => $registrant->registered_time,
                    'payment_status' => $registrant->payment_status ?? 'Pending',
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    protected function getCountForWeek(int $weekIndex): int
    {
        return count($this->getRegistrationsForWeek($weekIndex));
    }

    protected function getCountForMonth(string $yearMonth): int
    {
        $month = Carbon::createFromFormat('Y-m', $yearMonth);
        $startOfMonth = $month->copy()->startOfMonth();
        $endOfMonth = $month->copy()->endOfMonth();

        return Registrant::query()
            ->join('course_schedule', 'registrants.schedule_id', '=', 'course_schedule.schedule_id')
            ->whereDate('course_schedule.start', '>=', $startOfMonth)
            ->whereDate('course_schedule.start', '<=', $endOfMonth)
            ->count();
    }

    protected function getRegistrationsForMonth(): array
    {
        $month = Carbon::createFromFormat('Y-m', $this->currentMonth);
        $startOfMonth = $month->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $endOfMonth = $month->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        return Registrant::query()
            ->with(['schedule.course'])
            ->whereHas('schedule', function (Builder $query) use ($startOfMonth, $endOfMonth) {
                $query->whereDate('start', '<=', $endOfMonth)
                    ->whereDate('end', '>=', $startOfMonth);
            })
            ->orderBy('registered_time')
            ->get()
            ->map(function (Registrant $registrant) {
                $schedule = $registrant->schedule;
                if (!$schedule) {
                    return null;
                }

                $startDate = Carbon::parse($schedule->start);
                $endDate = Carbon::parse($schedule->end);

                return [
                    'id' => $registrant->registrants_id,
                    'name' => $registrant->full_name,
                    'course' => $schedule->course?->title ?? $registrant->title_course,
                    'location' => $schedule->location,
                    'start' => $startDate,
                    'end' => $endDate,
                    'registration_date' => $registrant->registered_time,
                    'payment_status' => $registrant->payment_status ?? 'Pending',
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    protected function getUpcomingRegistrations(): array
    {
        $currentMonth = Carbon::createFromFormat('Y-m', $this->currentMonth);
        $startOfMonth = $currentMonth->copy()->startOfMonth();
        $endOfMonth = $currentMonth->copy()->endOfMonth();

        return Registrant::query()
            ->with(['schedule.course'])
            ->join('course_schedule', 'registrants.schedule_id', '=', 'course_schedule.schedule_id')
            ->whereDate('course_schedule.start', '>=', $startOfMonth)
            ->whereDate('course_schedule.start', '<=', $endOfMonth)
            ->orderBy('course_schedule.start', 'asc')
            ->select('registrants.*')
            ->limit(10)
            ->get()
            ->map(function (Registrant $registrant) {
                $schedule = $registrant->schedule;
                if (!$schedule) {
                    return null;
                }

                return [
                    'id' => $registrant->registrants_id,
                    'name' => $registrant->full_name,
                    'course' => $schedule->course?->title ?? $registrant->title_course,
                    'start' => Carbon::parse($schedule->start)->format('M d'),
                    'end' => Carbon::parse($schedule->end)->format('M d'),
                    'location' => $schedule->location,
                    'registration_date' => $registrant->registered_time,
                ];
            })
            ->filter()
            ->values()
            ->toArray();
    }

    protected function getViewData(): array
    {
        $weeks = $this->getWeeksInMonth();
        $weeksWithCounts = array_map(function ($week, $index) {
            $week['count'] = $this->getCountForWeek($index);
            $week['index'] = $index;
            return $week;
        }, $weeks, array_keys($weeks));

        return [
            'weeks' => $weeksWithCounts,
            'registrations' => $this->selectedWeekIndex !== null ? $this->getRegistrationsForWeek($this->selectedWeekIndex) : [],
            'selectedWeekLabel' => $this->selectedWeekIndex !== null ? $weeks[$this->selectedWeekIndex]['label'] : null,
            'upcoming' => $this->getUpcomingRegistrations(),
            'upcomingCount' => count($this->getUpcomingRegistrations()),
            'currentMonthLabel' => Carbon::createFromFormat('Y-m', $this->currentMonth)->format('F Y'),
            'monthCounts' => array_map(function ($month) {
                return $this->getCountForMonth($month);
            }, $this->generateMonthsForYear($this->selectedYear ?? now()->format('Y'))),
        ];
    }

    protected function generateMonthsForYear(string $year): array
    {
        $months = [];
        for ($month = 1; $month <= 12; $month++) {
            $months[] = sprintf('%s-%02d', $year, $month);
        }
        return $months;
    }
}