# Library Attendance Web Kiosk Access

The Attendance System is now available as a **Web Kiosk**, accessible from any device (Android, Windows, iOS, Mac) via a web browser.

## URL
**`http://[SERVER_IP]:8001/kiosk`**
*(Replace `[SERVER_IP]` with the IP address of the computer running the backend)*

---

## 📱 Android Access (Mobile)
1.  **Connect** your Android device to the same Wi-Fi network as the server.
2.  **Open Chrome** and navigate to the URL above.
3.  **Install App**: Tap the **Menu (⋮)** > **"Add to Home Screen"** or **"Install App"**.
4.  **Launch**: Open the new "Library Attendance" icon from your home screen.

---

## 💻 Windows Access (Desktop/Laptop)
You can use any Windows computer as an attendance station.

### Option 1: Web Browser
1.  Open **Chrome** or **Edge**.
2.  Navigate to `http://localhost:8001/kiosk` (if on the same PC) or the server's IP.
3.  **Allow Camera**: Click "Allow" if prompted to use the webcam for QR scanning.
4.  **USB Scanner**: If you have a USB Barcode/QR Scanner connected:
    -   Simply scan a card. The system automatically detects scanner input.
    -   Ensure the scanner is configured to send an "Enter" key after scanning (most are by default).

### Option 2: Install as Windows App (PWA)
1.  In **Chrome/Edge**, while on the Kiosk page:
    -   **Chrome**: Click the "Install" icon in the address bar (computer with down arrow).
    -   **Edge**: Click "App available. Install Library Attendance" in the address bar.
2.  This will create a desktop shortcut and launch the Kiosk in its own standalone window, removing browser distractions.

---

## 🛠️ Prerequisites
1.  **Start Backend**:
    -   Double-click **`LibraryServer.exe`** in the `New Backend Server` folder.
    -   Wait for the Admin Dashboard window to open.
    -   The server is now running on port 8001.
2.  **Start Frontend Assets** (Optional):
    -   For development only. In production, assets are served by the backend.

## Troubleshooting
-   **"Camera access denied"**: Check browser permissions (lock icon in address bar).
-   **"Connection refused"**: Ensure the backend is running and you are using the correct IP address.
-   **Scanner not typing**: Click anywhere on the page to ensure the window is focused.
