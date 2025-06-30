<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DiagnoseUploadCommand extends Command
{
    protected $signature = 'diagnose:upload';
    protected $description = 'Diagnose file upload configuration and issues';

    public function handle(): int
    {
        $this->info("🔍 Diagnosing file upload configuration...");
        
        // Check 1: PHP Upload Settings
        $this->info("1️⃣ Checking PHP upload settings:");
        $this->checkPhpSettings();
        
        // Check 2: Directory Permissions
        $this->info("\n2️⃣ Checking directory permissions:");
        $this->checkDirectoryPermissions();
        
        // Check 3: Laravel Configuration
        $this->info("\n3️⃣ Checking Laravel configuration:");
        $this->checkLaravelConfig();
        
        // Check 4: Storage Links
        $this->info("\n4️⃣ Checking storage links:");
        $this->checkStorageLinks();
        
        // Check 5: Recommendations
        $this->info("\n💡 Recommendations:");
        $this->showRecommendations();
        
        return 0;
    }

    private function checkPhpSettings(): void
    {
        $settings = [
            'file_uploads' => ini_get('file_uploads'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'max_input_time' => ini_get('max_input_time'),
            'max_file_uploads' => ini_get('max_file_uploads'),
            'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: 'Default system temp directory',
        ];

        foreach ($settings as $setting => $value) {
            $status = $this->getSettingStatus($setting, $value);
            $this->line("   {$setting}: {$value} {$status}");
        }

        // Check if upload_max_filesize is smaller than post_max_size
        $uploadSize = $this->parseSize(ini_get('upload_max_filesize'));
        $postSize = $this->parseSize(ini_get('post_max_size'));
        
        if ($uploadSize > $postSize) {
            $this->warn("   ⚠️  upload_max_filesize ({$settings['upload_max_filesize']}) is larger than post_max_size ({$settings['post_max_size']})");
            $this->info("   📝 post_max_size should be larger than upload_max_filesize");
        }
    }

    private function getSettingStatus(string $setting, string $value): string
    {
        switch ($setting) {
            case 'file_uploads':
                return $value ? '✅' : '❌ File uploads disabled!';
            
            case 'upload_max_filesize':
                $bytes = $this->parseSize($value);
                if ($bytes < 1048576) { // < 1MB
                    return '⚠️  Very small limit';
                } elseif ($bytes < 10485760) { // < 10MB
                    return '⚠️  Small limit for documents';
                } else {
                    return '✅';
                }
            
            case 'memory_limit':
                if ($value === '-1') {
                    return '✅ No limit';
                }
                $bytes = $this->parseSize($value);
                return $bytes >= 134217728 ? '✅' : '⚠️  Low memory limit'; // 128MB
            
            case 'max_execution_time':
                $time = (int) $value;
                if ($time === 0) {
                    return '✅ No limit';
                }
                return $time >= 300 ? '✅' : '⚠️  Short execution time'; // 5 minutes
            
            default:
                return '✅';
        }
    }

    private function checkDirectoryPermissions(): void
    {
        $directories = [
            'storage/app' => storage_path('app'),
            'storage/app/public' => storage_path('app/public'),
            'storage/app/public/documents' => storage_path('app/public/documents'),
            'storage/logs' => storage_path('logs'),
            'bootstrap/cache' => base_path('bootstrap/cache'),
        ];

        foreach ($directories as $name => $path) {
            if (!file_exists($path)) {
                $this->warn("   ❌ {$name}: Directory doesn't exist");
                $this->info("      📝 Create with: mkdir -p {$path}");
                continue;
            }

            $perms = substr(sprintf('%o', fileperms($path)), -4);
            $writable = is_writable($path);
            $readable = is_readable($path);

            $status = $writable && $readable ? '✅' : '❌';
            $this->line("   {$status} {$name}: {$perms} " . ($writable ? 'writable' : 'not writable') . ", " . ($readable ? 'readable' : 'not readable'));

            if (!$writable) {
                $this->info("      📝 Fix with: chmod 755 {$path}");
            }
        }
    }

    private function checkLaravelConfig(): void
    {
        // Check filesystem configuration
        $defaultDisk = config('filesystems.default');
        $publicDisk = config('filesystems.disks.public');
        
        $this->line("   Default filesystem: {$defaultDisk} ✅");
        $this->line("   Public disk root: " . ($publicDisk['root'] ?? 'Not configured') . " ✅");
        
        // Check if public disk is properly configured
        if (!isset($publicDisk['root'])) {
            $this->warn("   ❌ Public disk not properly configured");
        }

        // Check queue configuration
        $queueConnection = config('queue.default');
        $this->line("   Queue connection: {$queueConnection} " . ($queueConnection === 'sync' ? '✅' : '⚠️  Consider using sync for immediate processing'));
    }

    private function checkStorageLinks(): void
    {
        $publicPath = public_path('storage');
        $storagePath = storage_path('app/public');

        if (!file_exists($publicPath)) {
            $this->warn("   ❌ Storage link doesn't exist");
            $this->info("      📝 Create with: php artisan storage:link");
        } elseif (!is_link($publicPath)) {
            $this->warn("   ⚠️  Storage path exists but is not a symlink");
            $this->info("      📝 Remove and recreate: rm -rf public/storage && php artisan storage:link");
        } else {
            $linkTarget = readlink($publicPath);
            if (realpath($linkTarget) === realpath($storagePath)) {
                $this->line("   ✅ Storage link is correctly configured");
            } else {
                $this->warn("   ❌ Storage link points to wrong location");
                $this->info("      Current: {$linkTarget}");
                $this->info("      Expected: {$storagePath}");
                $this->info("      📝 Fix with: rm public/storage && php artisan storage:link");
            }
        }
    }

    private function showRecommendations(): void
    {
        $uploadSize = $this->parseSize(ini_get('upload_max_filesize'));
        $postSize = $this->parseSize(ini_get('post_max_size'));
        $memoryLimit = $this->parseSize(ini_get('memory_limit'));

        $this->line("   📋 For better file upload support:");
        
        if ($uploadSize < 10485760) { // < 10MB
            $this->info("   • Increase upload_max_filesize to at least 10M in php.ini");
        }
        
        if ($postSize <= $uploadSize) {
            $this->info("   • Set post_max_size to be larger than upload_max_filesize");
        }
        
        if ($memoryLimit > 0 && $memoryLimit < 268435456) { // < 256MB
            $this->info("   • Consider increasing memory_limit to 256M or higher");
        }
        
        $maxExecutionTime = (int) ini_get('max_execution_time');
        if ($maxExecutionTime > 0 && $maxExecutionTime < 300) {
            $this->info("   • Increase max_execution_time to 300 or 0 (unlimited)");
        }

        $this->line("\n   🔧 Example php.ini settings for large file uploads:");
        $this->info("   upload_max_filesize = 100M");
        $this->info("   post_max_size = 110M");
        $this->info("   memory_limit = 256M");
        $this->info("   max_execution_time = 300");
        $this->info("   max_input_time = 300");
        
        $this->line("\n   ⚠️  Don't forget to restart your web server after changing php.ini!");
        
        // Check if we're in a development environment
        if (app()->environment('local')) {
            $this->line("\n   🏠 For local development (XAMPP/WAMP/MAMP):");
            $this->info("   • Edit php.ini in your PHP installation directory");
            $this->info("   • Restart Apache/Nginx");
            $this->info("   • Check with: php -i | grep upload_max_filesize");
        }
    }

    private function parseSize(string $size): int
    {
        if ($size === '-1') {
            return PHP_INT_MAX;
        }
        
        $size = trim($size);
        $last = strtolower($size[strlen($size) - 1]);
        $size = (int) $size;

        switch ($last) {
            case 'g': $size *= 1024;
            case 'm': $size *= 1024;
            case 'k': $size *= 1024;
        }

        return $size;
    }
}