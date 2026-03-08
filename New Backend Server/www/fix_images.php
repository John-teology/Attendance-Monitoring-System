<?php
// fix_images.php

// Define paths
$baseDir = __DIR__;
$dbPath = $baseDir . '/database/database.sqlite';
$storageDir = $baseDir . '/storage/app/public/branding';
$publicStorageDir = $baseDir . '/public/storage';

// Ensure branding directory exists
if (!file_exists($storageDir)) {
    mkdir($storageDir, 0777, true);
}

// Default images
$defaultLogo = 'logo.png';
$defaultBg = 'background.jpg';

// Download defaults if they don't exist
if (!file_exists("$storageDir/$defaultLogo")) {
    echo "Downloading default logo...\n";
    file_put_contents("$storageDir/$defaultLogo", file_get_contents("https://placehold.co/200x200/png?text=Logo"));
}
if (!file_exists("$storageDir/$defaultBg")) {
    echo "Downloading default background...\n";
    file_put_contents("$storageDir/$defaultBg", file_get_contents("https://placehold.co/1920x1080/jpg?text=Background"));
}

// Connect to SQLite
try {
    $pdo = new PDO("sqlite:$dbPath");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Fetch settings
    $stmt = $pdo->query("SELECT id, school_logo, app_background_image FROM system_settings LIMIT 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        // Create default row if missing
        $pdo->exec("INSERT INTO system_settings (school_name, school_logo, app_background_image, created_at, updated_at) VALUES ('Saint Mary\'s Academy', 'branding/$defaultLogo', 'branding/$defaultBg', datetime('now'), datetime('now'))");
        echo "Created default system settings.\n";
    } else {
        $updates = [];
        
        // Check Logo
        $currentLogo = $settings['school_logo'];
        if (!$currentLogo || !file_exists($baseDir . '/storage/app/public/' . $currentLogo)) {
            echo "Fixing broken/missing logo...\n";
            $updates[] = "school_logo = 'branding/$defaultLogo'";
        }
        
        // Check Background
        $currentBg = $settings['app_background_image'] ?? null; // Might be missing in older migrations
        if (!$currentBg || !file_exists($baseDir . '/storage/app/public/' . $currentBg)) {
            echo "Fixing broken/missing background...\n";
            $updates[] = "app_background_image = 'branding/$defaultBg'";
        }
        
        if (!empty($updates)) {
            $sql = "UPDATE system_settings SET " . implode(', ', $updates) . " WHERE id = " . $settings['id'];
            $pdo->exec($sql);
            echo "Database updated with default assets.\n";
        } else {
            echo "Assets verify OK.\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    // If column app_background_image is missing, we might ignore or run migration. 
    // But repair_app.bat runs migrations first, so it should be fine.
}
?>
