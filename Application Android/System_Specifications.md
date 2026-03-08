# Library Entry Attendance System - Technical Specifications & Functional Details

## 1. Executive Summary
The Library Entry Attendance System is a comprehensive solution designed to streamline the tracking of student and faculty attendance in a library setting. It consists of two primary components:
1.  **Android Kiosk Application:** A dedicated, offline-capable tablet app used at the library entrance for scanning IDs.
2.  **Web Administration Dashboard:** A centralized server application for managing users, viewing reports, and configuring system settings.

The system supports **hybrid operation**, allowing the Android app to function fully without an internet connection and synchronize data automatically once connectivity is restored.

---

## 2. System Architecture

### 2.1 Technology Stack
| Component | Technology Used | Description |
| :--- | :--- | :--- |
| **Mobile App** | **Kotlin** | Native Android development |
| **Local Database** | **Room (SQLite)** | Robust offline data persistence |
| **Networking** | **Retrofit** | REST API communication with backend |
| **Web Backend** | **Laravel 11 (PHP)** | Secure, scalable API and admin interface |
| **Web Database** | **MySQL / SQLite** | Centralized data storage |
| **Web Frontend** | **Blade & Bootstrap 5** | Responsive admin dashboard |

### 2.2 Data Synchronization Flow
1.  **User Sync (Downstream):** The Android app periodically downloads the full list of registered users (Students/Faculty) from the server to allow offline ID verification.
2.  **Attendance Sync (Upstream):** 
    *   **Real-time:** When online, every scan is sent immediately to the server for validation (e.g., checking for "Too fast" duplicates).
    *   **Offline Fallback:** If the server is unreachable, scans are saved locally. A background worker automatically uploads these pending logs once the connection is restored.
3.  **Config Sync:** School name and logo updates made on the web admin are automatically pulled by the Android app.

---

## 3. Functional Specifications

### 3.1 Android Kiosk Application
**Core Function:** Serves as the user interface for students/faculty to log in/out.

#### **A. Scanning Modes**
*   **QR Code Mode:**
    *   Designed for external USB/Bluetooth scanners acting as keyboards.
    *   Accepts rapid input streams and processes them upon receiving an 'Enter' keystroke.
    *   Visual Feedback: "Scanning Standby..." with a wave animation.
*   **NFC/RFID Mode:**
    *   Uses the device's built-in NFC hardware.
    *   Supports reading standardized **NDEF Text Records** or falling back to the physical **UID** of the card.
    *   Visual Feedback: "Ready for Tap..." with a green theme.

#### **B. User Feedback System**
*   **Success:** Displays a green dialog with the user's name, timestamp, and entry type (Time In / Time Out).
*   **Failure:** Displays a red error dialog for invalid IDs or unknown users.
*   **Warning:** Displays an orange "Already Recorded" dialog if a user tries to scan again within 60 seconds (anti-duplicate protection).

#### **C. Administrative Features (Hidden)**
*   **Secret Menu:** Accessed by tapping the "School Name" header 5 times rapidly.
*   **PIN Protection:** Secured by a default PIN (`1234`).
*   **Server Configuration:** Allows changing the API Endpoint IP address without reinstalling the app.

---

### 3.2 Web Admin Dashboard
**Core Function:** Central management console for librarians and administrators.

#### **A. Dashboard & Analytics**
*   **Real-time Counters:** Shows "Active Users" (currently inside) and "Total Entries" for the day.
*   **Visual Charts:** (Planned/Extensible) Graphing attendance trends over time.

#### **B. User Management**
*   **Registration:** Add, edit, or delete students and faculty members.
*   **Import/Export:** Bulk import users via CSV or export current lists.
*   **QR Generation:** Generate and download printable QR codes for users.

#### **C. Reports & Logs**
*   **Attendance History:** View detailed logs of all entries and exits.
*   **Filtering:** Filter logs by date, user type, or specific name.
*   **Export:** Download attendance reports as Excel/CSV files.

#### **D. System Settings (Super Admin Only)**
*   **School Branding:**
    *   **Logo Upload:** Upload a custom PNG/JPG logo (supports transparency).
    *   **Name Update:** Change the displayed school/library name.
    *   **Auto-Sync:** Changes reflect immediately on the Web Dashboard and propagate to Android Apps.
*   **Admin Management:** Create and manage other admin accounts with role-based permissions (Super Admin, Admin, Editor, Viewer).

---

## 4. Technical Constraints & Requirements

### 4.1 Hardware Requirements
*   **Android Tablet:** Android 8.0+ recommended, NFC support required for RFID features.
*   **Scanner:** Optional external USB QR/Barcode scanner (HID mode).
*   **Server:** Any PC or Cloud Server running PHP 8.2+, Composer, and a database (MySQL/SQLite).

### 4.2 Network Requirements
*   **Local Network (LAN):** The app and server must be on the same network (Wi-Fi/Ethernet) for synchronization.
*   **Offline Tolerance:** The system functions 100% for scanning logs without network, but requires network for initial user syncing and log uploading.

### 4.3 Security
*   **API Security:** All API requests are protected by a custom `X-API-SECRET` header.
*   **Storage:** Android logs are stored in a private internal database.
*   **Validation:** Server-side validation prevents oversized file uploads and ensures data integrity.

---

## 5. Deployment Guide (Brief)

1.  **Server Setup:**
    *   Install PHP & Composer.
    *   Run `composer install` & `npm install`.
    *   Configure `.env` database connection.
    *   Run `php artisan migrate --seed` to setup database.
    *   Start server: `php artisan serve --host=0.0.0.0`.

2.  **App Setup:**
    *   Install APK on Android tablet.
    *   Connect to the same Wi-Fi as the server.
    *   Open **Secret Menu** (5 taps on title) -> Enter PIN `1234`.
    *   Enter Server IP (e.g., `192.168.1.100`) and Save.
    *   App will sync and be ready for use.
