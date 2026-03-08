Library Attendance System - Backend Server
========================================

To start the server and admin dashboard:
1. Double-click "LibraryServer.exe".
2. The application window will open, showing the Admin Dashboard.
3. The server is now running on http://localhost:8001.

To access the Web Kiosk (for Android/Windows clients):
1. Ensure "LibraryServer.exe" is running.
2. On any device connected to the same Wi-Fi, open a browser.
3. Go to: http://[YOUR_IP_ADDRESS]:8001/kiosk
   (e.g., http://192.168.1.5:8001/kiosk)

Troubleshooting:
- If you see "ViteManifestNotFoundException", try running `npm run build` in the `www` folder.
- If the app doesn't start or shows a firewall error:
  - Check if "LibraryServer.exe" is already running in Task Manager.
  - Ensure no other application is using port 8001.
  - Allow "LibraryServer.exe" through your firewall if prompted.
- If it fails to launch on a new computer, install the "Visual C++ Redistributable for Visual Studio 2015-2022" (x64).
