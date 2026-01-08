<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registrant;
use App\Models\Course;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class RegistrantController extends Controller
{
    /**
     * Display a listing of registrants.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Registrant::with('course:id,title');

            // Apply filters
            if ($request->has('course_id')) {
                $query->where('course_id', $request->course_id);
            }

            if ($request->has('payment_status')) {
                if ($request->payment_status === 'paid') {
                    $query->paid();
                } elseif ($request->payment_status === 'pending') {
                    $query->pending();
                }
            }

            if ($request->has('country')) {
                $query->byCountry($request->country);
            }

            if ($request->has('search')) {
                $query->where(function($q) use ($request) {
                    $q->where('firstname', 'LIKE', "%{$request->search}%")
                      ->orWhere('surname', 'LIKE', "%{$request->search}%")
                      ->orWhere('personal_email', 'LIKE', "%{$request->search}%")
                      ->orWhere('organization', 'LIKE', "%{$request->search}%");
                });
            }

            // Apply sorting
            $sortBy = $request->get('sort_by', 'registered_time');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginate results
            $perPage = min($request->get('per_page', 15), 50);
            $registrants = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $registrants->items(),
                'pagination' => [
                    'current_page' => $registrants->currentPage(),
                    'last_page' => $registrants->lastPage(),
                    'per_page' => $registrants->perPage(),
                    'total' => $registrants->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch registrants',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created registrant.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'course_id' => 'required|exists:course_event,id',
                'firstname' => 'required|string|max:255',
                'surname' => 'required|string|max:255',
                'secondname' => 'nullable|string|max:255',
                'personal_email' => 'required|email|max:255',
                'phone' => 'nullable|string|max:255',
                'organization' => 'nullable|string|max:255',
                'country' => 'nullable|string|max:255',
                'registrant_title' => 'nullable|string|max:255',
                'academic_qualification' => 'nullable|string|max:255',
                'department' => 'nullable|string|max:255',
                'mode_of_payment' => 'nullable|string|max:255',
                'total_amount' => 'nullable|numeric|min:0',
                'accommodation' => 'nullable|in:Yes,No',
                'airport_pickup' => 'nullable|in:Yes,No',
                'expectations' => 'nullable|string|max:255',
                'comment' => 'nullable|string|max:255',
                'how_you_heard' => 'nullable|string|max:255',
            ]);

            // Get course details
            $course = Course::findOrFail($validated['course_id']);
            
            // Add additional fields
            $validated['title_course'] = $course->title;
            $validated['registered_time'] = now()->format('Y-m-d H:i:s');
            $validated['registrant_no'] = 'REG-' . time() . '-' . rand(1000, 9999);
            $validated['invoice_no'] = 'INV-' . time() . '-' . rand(1000, 9999);

            $registrant = Registrant::create($validated);

            // Ensure a matching Lead exists with phone number captured
            if (!empty($registrant->personal_email)) {
                Lead::updateOrCreate(
                    ['email' => $registrant->personal_email],
                    [
                        'name' => trim($registrant->firstname . ' ' . ($registrant->secondname ?? '') . ' ' . $registrant->surname),
                        'phone' => $registrant->phone ?? null,
                        'interest' => $registrant->title_course ?? null,
                        'source' => 'Registration',
                        'status' => 'New',
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'message' => 'Registration successful',
                'data' => $registrant->load('course:id,title')
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Display the specified registrant.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $registrant = Registrant::with('course:id,title,start,end,location')
                                  ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $registrant
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registrant not found',
                'error' => config('app.debug') ? $e->getMessage() : 'Registrant not found'
            ], 404);
        }
    }

    /**
     * Get registrant statistics.
     */
    public function stats(): JsonResponse
    {
        try {
            $totalRegistrants = Registrant::count();
            $paidRegistrants = Registrant::paid()->count();
            $pendingRegistrants = Registrant::pending()->count();
            $recentRegistrants = Registrant::recent(7)->count();
            $totalRevenue = Registrant::paid()->sum('total_amount');

            // Top countries
            $topCountries = Registrant::selectRaw('country, COUNT(*) as count')
                                    ->whereNotNull('country')
                                    ->where('country', '!=', '')
                                    ->groupBy('country')
                                    ->orderBy('count', 'desc')
                                    ->limit(10)
                                    ->get();

            // Top organizations
            $topOrganizations = Registrant::selectRaw('organization, COUNT(*) as count')
                                        ->whereNotNull('organization')
                                        ->where('organization', '!=', '')
                                        ->groupBy('organization')
                                        ->orderBy('count', 'desc')
                                        ->limit(10)
                                        ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_registrants' => $totalRegistrants,
                    'paid_registrants' => $paidRegistrants,
                    'pending_registrants' => $pendingRegistrants,
                    'recent_registrants' => $recentRegistrants,
                    'total_revenue' => $totalRevenue,
                    'top_countries' => $topCountries,
                    'top_organizations' => $topOrganizations,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch registrant statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}