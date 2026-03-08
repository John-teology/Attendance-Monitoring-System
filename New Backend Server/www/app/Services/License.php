<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class License
{
    public static function valid()
    {
        if (!Storage::exists('license.key') || !Storage::exists('.hardware')) {
            return false;
        }

        // Integrity Check: Ensure this file hasn't been tampered with
        if (!self::checkIntegrity()) {
            return false;
        }

        $storedHardware = Storage::get('.hardware');
        $currentHardware = self::getHardwareFingerprint();

        return $storedHardware === $currentHardware;
    }

    public static function checkIntegrity()
    {
        // If no integrity hash is stored, we skip (or fail, depending on strictness)
        // For Step 9, we assume it's created on Bind
        if (!Storage::exists('.license_integrity')) {
            return true; // Pass if not yet bound/setup to avoid lockout before binding
        }

        $storedHash = Storage::get('.license_integrity');
        $currentHash = self::getServiceHash();

        return $storedHash === $currentHash;
    }

    public static function getServiceHash()
    {
        return hash_file('sha256', __FILE__);
    }

    public static function getHardwareFingerprint()
    {
        // Simple hardware fingerprint based on machine name and OS
        return hash('sha256', php_uname('n') . php_uname('s') . php_uname('r') . php_uname('m'));
    }
}
