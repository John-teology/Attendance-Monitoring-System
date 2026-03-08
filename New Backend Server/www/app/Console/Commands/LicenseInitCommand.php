<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Services\License;
use App\Services\DatabaseGuard;

class LicenseInitCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'license:init {--force : Force re-initialization even if valid}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize or Repair the Application License and Security Bindings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting System Initialization...');

        // Check validation manually to avoid Storage facade dependencies if broken
        $licensePath = storage_path('app/license.key');
        $hardwarePath = storage_path('app/.hardware');
        
        $isValid = file_exists($licensePath) && file_exists($hardwarePath);
        if ($isValid) {
            $stored = file_get_contents($hardwarePath);
            $current = License::getHardwareFingerprint();
            if ($stored === $current) {
                if (!$this->option('force')) {
                    $this->info('System is already licensed and secure.');
                    return 0;
                }
            }
        }

        try {
            // Ensure storage/app exists
            if (!is_dir(storage_path('app'))) {
                mkdir(storage_path('app'), 0755, true);
            }

            // 1. Generate Hardware Fingerprint
            $hardwareFingerprint = License::getHardwareFingerprint();
            $this->line("Hardware Fingerprint: <comment>$hardwareFingerprint</comment>");

            // 2. Create License Key
            $licenseKey = 'LIC-' . strtoupper(uniqid()) . '-' . date('Ymd');
            $this->line("Generated License Key: <comment>$licenseKey</comment>");

            // 3. Store .hardware
            file_put_contents(storage_path('app/.hardware'), $hardwareFingerprint);

            // 4. Create license.key
            file_put_contents(storage_path('app/license.key'), $licenseKey);

            // 5. Create .lock
            file_put_contents(storage_path('app/.lock'), 'LOCKED-' . date('Y-m-d H:i:s'));

            // 6. Store .db_fingerprint
            $dbFingerprint = DatabaseGuard::getDatabaseFingerprint();
            file_put_contents(storage_path('app/.db_fingerprint'), $dbFingerprint);
            $this->line("Database Fingerprint Secured.");

            // 7. Store .license_integrity
            file_put_contents(storage_path('app/.license_integrity'), License::getServiceHash());
            $this->line("License Service Integrity Secured.");

            // 8. Log Activation
            $logMessage = '[' . date('Y-m-d H:i:s') . '] SYSTEM ACTIVATED via CLI (Repair Tool)' . PHP_EOL;
            $logMessage .= "License: $licenseKey | Hardware: $hardwareFingerprint" . PHP_EOL;
            
            // Ensure log directory exists
            if (!file_exists(storage_path('logs'))) {
                mkdir(storage_path('logs'), 0755, true);
            }
            file_put_contents(storage_path('logs/license.log'), $logMessage, FILE_APPEND);

            $this->info('-------------------------------------------');
            $this->info(' SYSTEM SUCCESSFULLY BOUND AND ACTIVATED');
            $this->info('-------------------------------------------');

        } catch (\Exception $e) {
            $this->error('Failed to initialize system: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
