<?php

namespace App\Http\Controllers;

use App\Models\CsvImport;
use App\Services\CsvImportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CsvImportController extends Controller
{
    public function processImport(Request $request): JsonResponse
    {
        $importId = $request->input('import_id');
        $options = $request->input('options', []);
        
        $import = CsvImport::findOrFail($importId);
        
        try {
            $service = new CsvImportService();
            $result = $service->processImport($import, $options);
            
            return response()->json([
                'success' => $result['success'],
                'message' => $result['message'],
                'stats' => $result['stats'] ?? null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function getProgress(Request $request): JsonResponse
    {
        $importId = $request->input('import_id');
        $import = CsvImport::findOrFail($importId);
        
        return response()->json([
            'status' => $import->status,
            'total_rows' => $import->total_rows ?? 0,
            'processed_rows' => $import->processed_rows ?? 0,
            'successful_rows' => $import->successful_rows ?? 0,
            'failed_rows' => $import->failed_rows ?? 0,
            'progress_percentage' => $import->progress_percentage ?? 0,
            'success_rate' => $import->success_rate ?? 0,
            'duration' => $import->duration,
            'is_completed' => in_array($import->status, ['completed', 'failed']),
        ]);
    }

    public function downloadTemplate(string $type)
    {
        $templates = [
            'courses' => [
                'filename' => 'courses_template.csv',
                'headers' => ['Title', 'Category', 'Start Date', 'End Date', 'Registration Cutoff', 'Duration (Days)', 'Introduction', 'Course Content', 'Location', 'Price KES', 'Price USD', 'Price EUR', 'Price GBP'],
                'sample' => ['Data Analysis with R', 'Data Analysis', '2025-03-01', '2027-01-01', '2025-02-25', '5', 'Learn data analysis using R programming', 'Comprehensive course covering data manipulation, visualization, and statistical analysis using R programming language.', 'Nairobi', '25000', '250', '220', '200']
            ],
            'users' => [
                'filename' => 'users_template.csv',
                'headers' => ['First Name', 'Last Name', 'Email', 'Phone', 'Location'],
                'sample' => ['John', 'Doe', 'john.doe@example.com', '+254123456789', 'Nairobi']
            ],
            'leads' => [
                'filename' => 'leads_template.csv',
                'headers' => ['Name', 'Email', 'Phone', 'Interest', 'Source'],
                'sample' => ['Jane Smith', 'jane.smith@example.com', '+254987654321', 'Data Analysis Course', 'Website']
            ],
            'news' => [
                'filename' => 'news_template.csv',
                'headers' => ['Title', 'Category', 'Introduction', 'Content', 'Author'],
                'sample' => ['New Course Launch', 'Announcements', 'We are excited to announce...', 'Detailed article content goes here...', 'Admin']
            ]
        ];

        if (!isset($templates[$type])) {
            abort(404, 'Template not found');
        }

        $template = $templates[$type];
        $filename = $template['filename'];

        return response()->streamDownload(function () use ($template) {
            $handle = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($handle, $template['headers']);
            
            // Add sample data
            fputcsv($handle, $template['sample']);
            
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
