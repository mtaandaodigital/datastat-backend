<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\ExportController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/admin');
});

// Test route to check session functionality
Route::get('/test-session', function (Request $request) {
    // Test session storage
    $request->session()->put('test_key', 'Session is working!');
    $sessionValue = $request->session()->get('test_key');
    
    // Test authentication status
    $authStatus = auth()->check() ? 'Authenticated' : 'Not authenticated';
    $user = auth()->user();
    
    return response()->json([
        'session_test' => $sessionValue,
        'auth_status' => $authStatus,
        'user' => $user ? [
            'id' => $user->getAuthIdentifier(),
            'email' => $user->email,
            'name' => $user->name ?? 'N/A'
        ] : null,
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
    ]);
});

// Test route for CSS/Tailwind
Route::get('/test-css', function () {
    return view('test-css');
});

// Export routes
Route::middleware(['auth'])->group(function () {
    Route::get('/export/courses', [ExportController::class, 'exportCourses'])->name('export.courses');
    Route::get('/export/users', [ExportController::class, 'exportUsers'])->name('export.users');
    Route::get('/export/leads', [ExportController::class, 'exportLeads'])->name('export.leads');
    Route::get('/export/news', [ExportController::class, 'exportNews'])->name('export.news');
    Route::get('/export/registrants', [ExportController::class, 'exportRegistrants'])->name('export.registrants');
    Route::get('/export/trainers', [ExportController::class, 'exportTrainers'])->name('export.trainers');
});

// CSV Import routes
Route::middleware(['auth'])->group(function () {
    Route::post('/csv-import/process', [App\Http\Controllers\CsvImportController::class, 'processImport'])->name('csv-import.process');
    Route::get('/csv-import/progress', [App\Http\Controllers\CsvImportController::class, 'getProgress'])->name('csv-import.progress');
    Route::get('/csv-template/{type}', [App\Http\Controllers\CsvImportController::class, 'downloadTemplate'])->name('csv.template');
    
    // Upload CSV route (non-Livewire)
    Route::post('/admin/upload-csv', [App\Http\Controllers\CsvUploadController::class, 'upload'])->name('admin.upload-csv');
});



