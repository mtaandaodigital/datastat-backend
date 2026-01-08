<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EnsureUploadsDirectoryExists extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'uploads:ensure-directory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Ensures the uploads directory exists on the main site';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = Storage::disk('main_site_uploads');
        
        if (!$disk->exists('uploads')) {
            $disk->makeDirectory('uploads');
            $this->info('Created uploads directory on main site.');
        } else {
            $this->info('Uploads directory already exists on main site.');
        }
        
        // Check permissions
        $this->info('Ensuring proper permissions...');
        
        // This will be executed on the server
        // Using the correct server path
        $path = '/home/datasta/website/images/courses/';
        
        if (is_dir($path)) {
            // Set directory permissions to 755 (rwxr-xr-x)
            chmod($path, 0755);
            $this->info('Set directory permissions to 755.');
        } else {
            $this->error('Could not find the uploads directory at: ' . $path);
        }
        
        return Command::SUCCESS;
    }
}