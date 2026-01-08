<?php

namespace App\Http\Controllers;

use App\Services\CsvImportService;
use Illuminate\Http\Request;

class CsvTemplateController extends Controller
{
    protected $csvImportService;

    public function __construct(CsvImportService $csvImportService)
    {
        $this->csvImportService = $csvImportService;
    }

    public function downloadTemplate($type)
    {
        $template = $this->csvImportService->generateTemplate($type);
        
        if (empty($template['headers'])) {
            abort(404, 'Template not found');
        }
        
        $filename = $type . '_import_template.csv';
        
        $callback = function() use ($template) {
            $file = fopen('php://output', 'w');
            
            // Write headers
            fputcsv($file, $template['headers']);
            
            // Write sample data
            foreach ($template['sample_data'] as $row) {
                fputcsv($file, $row);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}