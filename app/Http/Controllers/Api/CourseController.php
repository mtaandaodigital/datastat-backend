<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CourseController extends Controller
{
    /**
     * Display a listing of active courses.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Course::active();

            // Apply filters
            if ($request->has('category')) {
                $query->byCategory($request->category);
            }

            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('title', 'LIKE', "%{$request->search}%")
                      ->orWhere('introduction', 'LIKE', "%{$request->search}%");
                });
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'start');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = min($request->get('per_page', 15), 50);
            $courses = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $courses->items(),
                'pagination' => [
                    'current_page' => $courses->currentPage(),
                    'last_page' => $courses->lastPage(),
                    'per_page' => $courses->perPage(),
                    'total' => $courses->total(),
                ],
                'filters' => [
                    'categories' => Course::getCategories(),
                    'locations' => Course::getLocations(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch courses',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified course.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $course = Course::with(['registrants' => function($query) {
                $query->select('registrants_id', 'course_id', 'firstname', 'surname', 'personal_email', 'total_amount', 'registered_time');
            }])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course,
                    'stats' => [
                        'total_registrants' => $course->registrants->count(),
                        'paid_registrants' => $course->registrants->where('total_amount', '>', 0)->count(),
                        'total_revenue' => $course->registrants->where('total_amount', '>', 0)->sum('total_amount'),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Course not found'
            ], 404);
        }
    }

    /**
     * Get course statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $totalCourses = Course::count();
            $activeCourses = Course::active()->count();
            $upcomingCourses = Course::upcoming()->count();
            $ongoingCourses = Course::ongoing()->count();
            $completedCourses = Course::completed()->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_courses' => $totalCourses,
                    'active_courses' => $activeCourses,
                    'upcoming_courses' => $upcomingCourses,
                    'ongoing_courses' => $ongoingCourses,
                    'completed_courses' => $completedCourses,
                    'categories' => Course::getCategories(),
                    'locations' => Course::getLocations(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch course statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}