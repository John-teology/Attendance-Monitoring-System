<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use App\Services\License;
use App\Services\DatabaseGuard;

class LicenseController extends Controller
{
    public function index()
    {
        $licenseExists = Storage::exists('license.key');
        $isBound = false;
        $hardwareMismatch = false;

        if ($licenseExists) {
            $storedHardware = Storage::get('.hardware');
            $currentHardware = License::getHardwareFingerprint();
            
            if ($storedHardware === $currentHardware) {
                $isBound = true;
            } else {
                $hardwareMismatch = true;
            }
        }

        return view('admin.license.index', compact('licenseExists', 'isBound', 'hardwareMismatch'));
    }

    public function bind(Request $request)
    {
        try {
            // 1. Generate Hardware Fingerprint
            $hardwareFingerprint = License::getHardwareFingerprint();
            
            // 2. Create License Key (Dummy content for now, or signed payload)
            $licenseKey = 'LIC-' . strtoupper(uniqid()) . '-' . date('Ymd');
            
            // 3. Store .hardware
            Storage::put('.hardware', $hardwareFingerprint);
            
            // 4. Create license.key
            Storage::put('license.key', $licenseKey);
            
            // 5. Create .lock
            Storage::put('.lock', 'LOCKED-' . date('Y-m-d H:i:s'));
            
            // 6. Store .db_fingerprint
            $dbFingerprint = DatabaseGuard::getDatabaseFingerprint();
            Storage::put('.db_fingerprint', $dbFingerprint);

            // 7. Store .license_integrity (Hash of License Service File)
            Storage::put('.license_integrity', License::getServiceHash());
            
            // 8. Log Activation
            $logMessage = '[' . date('Y-m-d H:i:s') . '] SYSTEM ACTIVATED by ' . (auth('admin')->user()->email ?? 'Unknown') . PHP_EOL;
            $logMessage .= "License: $licenseKey | Hardware: $hardwareFingerprint" . PHP_EOL;
            File::append(storage_path('logs/license.log'), $logMessage);

            return redirect()->route('admin.license.index')->with('success', 'Machine successfully bound. License generated.');
        } catch (\Exception $e) {
            return redirect()->route('admin.license.index')->with('error', 'Failed to bind machine: ' . $e->getMessage());
        }
    }

    public function rebind(Request $request)
    {
        // Log Rebind Action
        $logMessage = '[' . date('Y-m-d H:i:s') . '] SYSTEM REBOUND by ' . (auth('admin')->user()->email ?? 'Unknown') . PHP_EOL;
        File::append(storage_path('logs/license.log'), $logMessage);

        // Rebind logic is essentially the same as bind, but explicit action
        return $this->bind($request);
    }

    public function unbind(Request $request)
    {
        try {
            // Delete License and Security Files
            Storage::delete(['.hardware', 'license.key', '.lock', '.db_fingerprint', '.license_integrity']);

            // Log Unbind Action
            $logMessage = '[' . date('Y-m-d H:i:s') . '] SYSTEM UNBOUND by ' . (auth('admin')->user()->email ?? 'Unknown') . PHP_EOL;
            File::append(storage_path('logs/license.log'), $logMessage);

            return redirect()->route('admin.license.index')->with('success', 'Machine successfully unbound. Database is safe to move.');
        } catch (\Exception $e) {
            return redirect()->route('admin.license.index')->with('error', 'Failed to unbind machine: ' . $e->getMessage());
        }
    }
}
