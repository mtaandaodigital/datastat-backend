<?php

namespace App\Services;

use App\Models\CsvImport;
use App\Models\Course;
use App\Models\User;
use App\Models\Lead;
use App\Models\News;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;

class CsvImportService
{
    protected $batchSize = 100;
    
    public function processImport(CsvImport $import, array $options = []): array
    {
        try {
            $import->markAsStarted();
            
            $filePath = storage_path('app/csv-imports/' . $import->filename);
            
            if (!file_exists($filePath)) {
                $import->markAsFailed(['File not found']);
                return ['success' => false, 'message' => 'File not found'];
            }

            $handle = fopen($filePath, 'r');
            if (!$handle) {
                $import->markAsFailed(['Could not open file']);
                return ['success' => false, 'message' => 'Could not open file'];
            }

            $hasHeader = $options['has_header'] ?? true;
            $headers = [];
            $totalRows = 0;
            $processedRows = 0;
            $successfulRows = 0;
            $failedRows = 0;
            $errors = [];

            // Count total rows
            while (($row = fgetcsv($handle)) !== false) {
                $totalRows++;
            }
            
            if ($hasHeader) {
                $totalRows--; // Subtract header row
            }

            $import->update(['total_rows' => $totalRows]);

            // Reset file pointer
            rewind($handle);

            // Read headers if present
            if ($hasHeader) {
                $headers = fgetcsv($handle);
                $headers = array_map('trim', $headers);
            }

            // Process data row by row for real-time progress
            $rowNumber = $hasHeader ? 2 : 1; // Start from 2 if header exists

            while (($row = fgetcsv($handle)) !== false) {
                try {
                    $data = $this->mapRowData(array_map('trim', $row), $headers, $import->type);
                    $validation = $this->validateRowData($data, $import->type);

                    if (!$validation['valid']) {
                        $failedRows++;
                        $errors[] = [
                            'row' => $rowNumber,
                            'error' => implode(', ', $validation['errors']),
                            'timestamp' => now()->toISOString(),
                        ];
                    } else {
                        $result = $this->createRecord($data, $import->type);
                        
                        if ($result['success']) {
                            $successfulRows++;
                        } else {
                            $failedRows++;
                            $errors[] = [
                                'row' => $rowNumber,
                                'error' => $result['error'],
                                'timestamp' => now()->toISOString(),
                            ];
                        }
                    }

                    $processedRows++;
                    
                    // Update progress after each row for real-time updates
                    $import->updateProgress($processedRows, $successfulRows, $failedRows, $errors);

                } catch (\Exception $e) {
                    $failedRows++;
                    $processedRows++;
                    $errors[] = [
                        'row' => $rowNumber,
                        'error' => 'Exception: ' . $e->getMessage(),
                        'timestamp' => now()->toISOString(),
                    ];
                    
                    $import->updateProgress($processedRows, $successfulRows, $failedRows, $errors);
                }

                $rowNumber++;
            }

            fclose($handle);

            $import->updateProgress($processedRows, $successfulRows, $failedRows, $errors);
            $import->markAsCompleted();

            return [
                'success' => true,
                'message' => "Import completed. {$successfulRows} successful, {$failedRows} failed.",
                'stats' => [
                    'total' => $totalRows,
                    'processed' => $processedRows,
                    'successful' => $successfulRows,
                    'failed' => $failedRows,
                ]
            ];

        } catch (\Exception $e) {
            $import->markAsFailed(['Exception: ' . $e->getMessage()]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    protected function processBatch(CsvImport $import, array $batch, array $headers): array
    {
        $successful = 0;
        $failed = 0;
        $errors = [];

        foreach ($batch as $item) {
            try {
                $data = $this->mapRowData($item['data'], $headers, $import->type);
                $validation = $this->validateRowData($data, $import->type);

                if (!$validation['valid']) {
                    $failed++;
                    $errors[] = [
                        'row' => $item['row_number'],
                        'error' => implode(', ', $validation['errors']),
                        'timestamp' => now()->toISOString(),
                    ];
                    continue;
                }

                $result = $this->createRecord($data, $import->type);
                
                if ($result['success']) {
                    $successful++;
                } else {
                    $failed++;
                    $errors[] = [
                        'row' => $item['row_number'],
                        'error' => $result['error'],
                        'timestamp' => now()->toISOString(),
                    ];
                }

            } catch (\Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $item['row_number'],
                    'error' => 'Exception: ' . $e->getMessage(),
                    'timestamp' => now()->toISOString(),
                ];
            }
        }

        return [
            'successful' => $successful,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    protected function mapRowData(array $row, array $headers, string $type): array
    {
        $data = [];

        if (!empty($headers)) {
            // Map by headers
            foreach ($headers as $index => $header) {
                $data[$header] = $row[$index] ?? '';
            }
        } else {
            // Map by position based on type
            $data = $this->mapByPosition($row, $type);
        }

        return $data;
    }

    protected function mapByPosition(array $row, string $type): array
    {
        switch ($type) {
            case 'courses':
                return [
                    'title' => $row[0] ?? '',
                    'category' => $row[1] ?? '',
                    'start_date' => $row[2] ?? '',
                    'end' => $row[3] ?? '2027-01-01', // Fixed field name and default date
                    'cut_of_date' => $row[4] ?? '',
                    'no_of_days' => $row[5] ?? '',
                    'introduction' => $row[6] ?? '',
                    'body' => $row[7] ?? '',
                    'location' => $row[8] ?? '',
                    'fee_kes' => $row[9] ?? '', // Fixed field name
                    'fee_usd' => $row[10] ?? '', // Fixed field name
                    'fee_euro' => $row[11] ?? '', // Fixed field name
                    'fee_pound' => $row[12] ?? '', // Fixed field name
                ];

            case 'users':
                return [
                    'firstname' => $row[0] ?? '',
                    'surname' => $row[1] ?? '',
                    'email' => $row[2] ?? '',
                    'telephone' => $row[3] ?? '',
                    'location' => $row[4] ?? '',
                ];

            case 'leads':
                return [
                    'name' => $row[0] ?? '',
                    'email' => $row[1] ?? '',
                    'phone' => $row[2] ?? '',
                    'interest' => $row[3] ?? '',
                    'source' => $row[4] ?? 'CSV Import',
                ];

            case 'news':
                return [
                    'title' => $row[0] ?? '',
                    'category' => $row[1] ?? '',
                    'introduction' => $row[2] ?? '',
                    'body' => $row[3] ?? '',
                    'author' => $row[4] ?? '',
                ];

            default:
                return [];
        }
    }

    protected function validateRowData(array $data, string $type): array
    {
        $rules = $this->getValidationRules($type);
        
        $validator = Validator::make($data, $rules);

        return [
            'valid' => !$validator->fails(),
            'errors' => $validator->errors()->all(),
        ];
    }

    protected function getValidationRules(string $type): array
    {
        switch ($type) {
            case 'courses':
                return [
                    'title' => 'required|string|max:255',
                    'category' => 'required|string|max:255',
                    'start_date' => 'required|string|max:255', // VARCHAR field in database
                    'end' => 'required|string|max:255', // VARCHAR field in database
                    'cut_of_date' => 'required|string|max:255', // VARCHAR field in database
                    'no_of_days' => 'required|integer|min:1',
                    'introduction' => 'required|string',
                    'body' => 'required|string',
                    'location' => 'required|string|max:255',
                ];

            case 'users':
                return [
                    'firstname' => 'required|string|max:255',
                    'lastname' => 'required|string|max:255',
                    'email' => 'required|email|unique:usermanagement,email',
                ];

            case 'leads':
                return [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email',
                ];

            case 'news':
                return [
                    'title' => 'required|string|max:255',
                    'category' => 'required|string|max:255',
                    'body' => 'required|string',
                ];

            default:
                return [];
        }
    }

    protected function createRecord(array $data, string $type): array
    {
        try {
            switch ($type) {
                case 'courses':
                    // Use default end date if not provided or empty (as string since DB field is VARCHAR)
                    $endDate = !empty($data['end']) ? $data['end'] : '2027-01-01';
                    
                    $course = Course::create([
                        'title' => $data['title'],
                        'category' => $data['category'],
                        'start_date' => is_numeric($data['start_date'])
                            ? (int) $data['start_date']
                            : Carbon::parse($data['start_date'])->timestamp, // store UNIX timestamp
                        'start' => Carbon::parse($data['start_date'])->format('Y-m-d'), // DATE field
                        'end' => $endDate, // string date (Y-m-d)
                        'cut_of_date' => $data['cut_of_date'], // Keep as string for VARCHAR field  
                        'no_of_days' => (int) $data['no_of_days'],
                        'introduction' => $data['introduction'],
                        'body' => $data['body'],
                        'course_status' => 'General',
                        'location' => $data['location'],
                        'software' => $data['software'] ?? '',
                        'fee_kes' => !empty($data['fee_kes']) ? (float) $data['fee_kes'] : 0,
                        'fee_usd' => !empty($data['fee_usd']) ? (float) $data['fee_usd'] : 0,
                        'fee_euro' => !empty($data['fee_euro']) ? (float) $data['fee_euro'] : 0,
                        'fee_pound' => !empty($data['fee_pound']) ? (float) $data['fee_pound'] : 0,
                        'submitted_time' => now(),
                        'schedule_status' => 1,
                    ]);
                    break;

                case 'users':
                    $user = User::create([
                        'firstname' => $data['firstname'],
                        'surname' => $data['surname'],
                        'email' => $data['email'],
                        'telephone' => $data['telephone'] ?? '',
                        'location' => $data['location'] ?? '',
                        'password' => md5('password123'), // Default password (MD5 to match existing)
                        'accesslevel' => 1,
                        'user_level' => 0,
                        'login_active_status' => 1,
                        'activation_status' => 1,
                    ]);
                    break;

                case 'leads':
                    $lead = Lead::create([
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? '',
                        'interest' => $data['interest'] ?? '',
                        'source' => $data['source'] ?? 'CSV Import',
                        'status' => 'New',
                    ]);
                    break;

                case 'news':
                    $news = News::create([
                        'title' => $data['title'],
                        'category' => $data['category'],
                        'introduction' => $data['introduction'] ?? '',
                        'body' => $data['body'],
                        'author' => $data['author'] ?? 'Admin',
                        'slug' => Str::slug($data['title']),
                        'is_published' => true,
                        'published_at' => now(),
                    ]);
                    break;

                default:
                    return ['success' => false, 'error' => 'Unknown import type'];
            }

            return ['success' => true];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function generateTemplate(string $type): array
    {
        switch ($type) {
            case 'courses':
                return [
                    'headers' => [
                        'title', 'category', 'start_date', 'end_date', 'cut_of_date',
                        'no_of_days', 'introduction', 'body', 'location', 'shillings',
                        'usd', 'euro', 'pound', 'software'
                    ],
                    'sample_data' => [
                        [
                            'Advanced Data Analysis with Python',
                            'Information Technology',
                            '2024-03-15',
                            '2024-03-19',
                            '2024-03-10',
                            '5',
                            'Learn advanced data analysis techniques using Python',
                            'This comprehensive course covers advanced data analysis...',
                            'Nairobi, Kenya',
                            '50000',
                            '500',
                            '450',
                            '400',
                            'Python, Jupyter Notebook'
                        ]
                    ]
                ];

            case 'users':
                return [
                    'headers' => ['firstname', 'surname', 'email', 'telephone', 'location'],
                    'sample_data' => [
                        ['John', 'Doe', 'john.doe@example.com', '+254700000000', 'Nairobi']
                    ]
                ];

            case 'leads':
                return [
                    'headers' => ['name', 'email', 'phone', 'interest', 'source'],
                    'sample_data' => [
                        ['Jane Smith', 'jane@example.com', '+254700000001', 'Data Analysis Course', 'Website']
                    ]
                ];

            case 'news':
                return [
                    'headers' => ['title', 'category', 'introduction', 'body', 'author'],
                    'sample_data' => [
                        ['New Course Launch', 'Company News', 'We are excited to announce...', 'Full article content here...', 'Admin']
                    ]
                ];

            default:
                return ['headers' => [], 'sample_data' => []];
        }
    }
}