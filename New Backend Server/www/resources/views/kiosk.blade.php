<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Library Attendance Kiosk</title>
    <meta name="theme-color" content="#F3F4F6">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">
    
    <!-- Tailwind CSS (CDN for modern styling features) -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts: Inter & Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Poppins:wght@500;600;700&display=swap" rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                        display: ['Poppins', 'sans-serif'],
                    },
                    colors: {
                        primary: '#4F46E5', // Indigo 600
                        secondary: '#10B981', // Emerald 500
                        dark: '#111827',
                    },
                    animation: {
                        'blob': 'blob 7s infinite',
                        'scan': 'scan 2s linear infinite',
                        'fade-in-up': 'fadeInUp 0.5s ease-out',
                    },
                    keyframes: {
                        blob: {
                            '0%': { transform: 'translate(0px, 0px) scale(1)' },
                            '33%': { transform: 'translate(30px, -50px) scale(1.1)' },
                            '66%': { transform: 'translate(-20px, 20px) scale(0.9)' },
                            '100%': { transform: 'translate(0px, 0px) scale(1)' },
                        },
                        scan: {
                            '0%': { top: '0%' },
                            '50%': { top: '100%' },
                            '100%': { top: '0%' },
                        },
                        fadeInUp: {
                            '0%': { opacity: '0', transform: 'translateY(20px)' },
                            '100%': { opacity: '1', transform: 'translateY(0)' },
                        }
                    }
                }
            }
        }
    </script>

    <style>
        body {
            background-color: #F3F4F6;
            overflow: hidden;
        }
        
        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }
        
        .glass-panel {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(16px);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        /* Animated Background Mesh */
        .bg-mesh {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: -1;
            background: #F3F4F6;
            overflow: hidden;
        }
        
        .blob-1 { background: #C7D2FE; top: -10%; left: -10%; width: 50vw; height: 50vw; }
        .blob-2 { background: #A7F3D0; bottom: -10%; right: -10%; width: 50vw; height: 50vw; animation-delay: 2s; }
        .blob-3 { background: #E9D5FF; top: 40%; left: 40%; width: 40vw; height: 40vw; animation-delay: 4s; }

        .scanner-line {
            position: absolute;
            width: 100%;
            height: 2px;
            background: #10B981;
            box-shadow: 0 0 4px #10B981;
        }

        /* Hide Scrollbar */
        ::-webkit-scrollbar { width: 0px; background: transparent; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen relative">

    <!-- Background -->
    <div class="bg-mesh">
        @if(isset($settings) && $settings->app_background_image)
             <div class="absolute inset-0 bg-cover bg-center" style="background-image: url('{{ asset('storage/' . $settings->app_background_image) }}'); opacity: 0.5;"></div>
             <div class="absolute inset-0 bg-white/30 backdrop-blur-[2px]"></div>
        @else
            <div class="absolute rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob blob-1"></div>
            <div class="absolute rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob blob-2"></div>
            <div class="absolute rounded-full mix-blend-multiply filter blur-3xl opacity-70 animate-blob blob-3"></div>
        @endif
    </div>

    <!-- Main Container -->
    <div class="w-full max-w-5xl h-[90vh] flex flex-col md:flex-row gap-6 p-6 animate-fade-in-up">
        
        <!-- Left Panel: Info & Stats -->
        <div class="glass-panel rounded-3xl p-8 flex flex-col justify-between flex-1 md:max-w-md relative overflow-hidden">
            <!-- Decorative Circle -->
            <div class="absolute top-0 right-0 -mt-10 -mr-10 w-40 h-40 bg-gradient-to-br from-primary/20 to-secondary/20 rounded-full blur-2xl"></div>

            <!-- Header -->
            <div class="z-10 flex flex-col items-center text-center w-full">
                <div class="mb-4">
                    @if(isset($settings) && $settings->school_logo)
                        <img src="{{ asset('storage/' . $settings->school_logo) }}" alt="Logo" class="w-32 h-32 object-contain drop-shadow-sm">
                    @else
                        <div class="w-20 h-20 bg-white rounded-2xl shadow-sm flex items-center justify-center text-primary mx-auto">
                            <i class="fas fa-book-open text-4xl"></i>
                        </div>
                    @endif
                </div>
                
                <h1 class="font-display font-bold text-2xl text-dark leading-tight mb-4 w-full">
                    @if(isset($settings) && $settings->school_name)
                        {!! nl2br(e($settings->school_name)) !!}
                    @else
                        Library<br>Attendance
                    @endif
                </h1>

                <p class="text-gray-500 mb-8">Welcome! Please scan your ID to log your attendance.</p>
            </div>

            <!-- Stats / Clock -->
            <div class="z-10 space-y-6">
                <!-- Clock Card -->
                <div class="bg-white/60 rounded-2xl p-6 shadow-sm border border-white/50 backdrop-blur-sm">
                    <div class="text-gray-500 text-sm font-medium uppercase tracking-wider mb-1">Current Time</div>
                    <div id="clock" class="font-display font-bold text-5xl text-dark tracking-tight">00:00 AM</div>
                    <div id="date" class="text-gray-400 font-medium mt-1">Monday, January 1</div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <!-- Active Count (Today's Total) -->
                    <div class="bg-white/60 rounded-2xl p-5 shadow-sm border border-white/50 backdrop-blur-sm flex flex-col justify-between h-32 relative overflow-hidden group">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider z-10">Active Count</div>
                        <div class="flex items-end justify-between z-10">
                            <div id="active-count" class="font-display font-bold text-4xl text-dark">0</div>
                            <div class="w-10 h-10 bg-indigo-100 text-primary rounded-full flex items-center justify-center">
                                <i class="fas fa-user-check"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Ongoing Count (Currently In) -->
                    <div class="bg-white/60 rounded-2xl p-5 shadow-sm border border-white/50 backdrop-blur-sm flex flex-col justify-between h-32 relative overflow-hidden group">
                        <div class="text-gray-500 text-xs font-bold uppercase tracking-wider z-10">Ongoing Count</div>
                        <div class="flex items-end justify-between z-10">
                            <div id="ongoing-count" class="font-display font-bold text-4xl text-dark">0</div>
                            <div class="w-10 h-10 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center">
                                <i class="fas fa-walking"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="z-10 mt-8 text-center md:text-left">
                <div class="text-xs text-gray-400 font-medium">System Online • v2.0</div>
            </div>
        </div>

        <!-- Right Panel: Scanner -->
        <div class="glass-panel rounded-3xl p-6 flex flex-col flex-[1.5] relative" onclick="focusInput()">
            
            <!-- Mode Toggle (Segmented Control) -->
            <div class="bg-gray-200/50 p-1.5 rounded-xl flex mb-6 relative">
                <!-- Sliding Background Pill -->
                <div id="toggle-bg" class="absolute top-1.5 bottom-1.5 left-1.5 w-[calc(50%-6px)] bg-white rounded-lg shadow-sm transition-all duration-300 ease-out"></div>
                
                <button onclick="setMode('qr')" class="flex-1 relative z-10 py-3 text-sm font-semibold transition-colors duration-300 text-dark flex items-center justify-center gap-2" id="btn-qr">
                    <i class="fas fa-qrcode"></i>
                    <span>QR Scanner</span>
                </button>
                <button onclick="setMode('nfc')" class="flex-1 relative z-10 py-3 text-sm font-semibold transition-colors duration-300 text-gray-500 flex items-center justify-center gap-2" id="btn-nfc">
                    <i class="fas fa-wifi rotate-90"></i>
                    <span>NFC / ID</span>
                </button>
            </div>

            <!-- Scanner Area -->
            <div class="flex-1 relative bg-dark rounded-2xl overflow-hidden shadow-inner group flex items-center justify-center">
                
                <!-- QR View -->
                <div id="qr-view" class="absolute inset-0 flex flex-col items-center justify-center transition-opacity duration-500 bg-gradient-to-br from-gray-900 to-gray-800">
                    <div class="w-32 h-32 rounded-full border-4 border-white/10 flex items-center justify-center relative mb-6">
                        <div class="absolute inset-0 rounded-full border-4 border-t-emerald-500 animate-spin"></div>
                        <i class="fas fa-qrcode text-5xl text-white/50"></i>
                    </div>
                    <h3 class="text-white font-display font-bold text-2xl mb-2">Ready to Scan</h3>
                    <p class="text-gray-400">Scan your QR Code</p>
                </div>

                <!-- NFC View -->
                <div id="nfc-view" class="absolute inset-0 flex flex-col items-center justify-center opacity-0 pointer-events-none transition-opacity duration-500 bg-gradient-to-br from-gray-900 to-gray-800">
                    <div class="w-32 h-32 rounded-full border-4 border-white/10 flex items-center justify-center relative mb-6">
                        <div class="absolute inset-0 rounded-full border-4 border-t-emerald-500 animate-spin"></div>
                        <i class="fas fa-id-card text-5xl text-white/50"></i>
                    </div>
                    <h3 class="text-white font-display font-bold text-2xl mb-2">Ready to Tap</h3>
                    <p class="text-gray-400">Tap your ID card on the reader</p>
                </div>
            </div>

            <!-- Status Bar -->
            <div class="mt-6 flex items-center justify-center gap-3">
                <div id="status-dot" class="w-3 h-3 bg-emerald-500 rounded-full animate-pulse"></div>
                <span id="status-text" class="text-gray-600 font-medium">Scanner Ready</span>
            </div>
        </div>
    </div>

    <!-- Hidden Input for NFC -->
    <input type="text" id="manual-input" class="opacity-0 absolute top-0 left-0 h-0 w-0" autocomplete="off">

    <!-- Fullscreen Prompt (Optional Overlay) -->
    <div id="fullscreen-overlay" class="fixed inset-0 z-[60] bg-dark/90 flex items-center justify-center cursor-pointer transition-opacity duration-500" onclick="enterFullscreen()">
        <div class="text-center animate-pulse">
            <i class="fas fa-expand text-6xl text-white mb-4"></i>
            <h2 class="text-white font-display font-bold text-2xl">Tap to Start Kiosk</h2>
            <p class="text-gray-400 mt-2">Enter Fullscreen Mode</p>
        </div>
    </div>

    <!-- Result Modal (Modern) -->
    <div id="result-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
        <!-- Backdrop -->
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
        
        <!-- Modal Card -->
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm relative z-10 overflow-hidden transform transition-all scale-100 animate-fade-in-up">
            <!-- Header Pattern -->
            <div class="h-32 bg-gradient-to-br from-primary to-purple-600 relative">
                <div class="absolute bottom-0 left-1/2 transform -translate-x-1/2 translate-y-1/2">
                    <div class="w-24 h-24 bg-white rounded-full p-1 shadow-xl">
                        <img id="modal-photo" src="" alt="User" class="w-full h-full rounded-full object-cover bg-gray-100">
                    </div>
                </div>
            </div>
            
            <!-- Content -->
            <div class="pt-16 pb-8 px-8 text-center">
                <h2 id="modal-name" class="font-display font-bold text-2xl text-dark mb-1">Student Name</h2>
                <p id="modal-id" class="text-gray-500 font-medium mb-4">ID: 123456</p>
                
                <div class="bg-gray-50 rounded-xl p-4 mb-6 border border-gray-100">
                    <div class="flex items-center justify-center gap-2 mb-1">
                        <i id="modal-icon" class="fas fa-check-circle text-emerald-500"></i>
                        <span id="modal-title" class="font-bold text-emerald-600 uppercase tracking-wide text-sm">Success</span>
                    </div>
                    <p id="modal-message" class="text-gray-600 text-sm">Successfully Logged In</p>
                </div>

                <button onclick="closeModal()" class="w-full bg-dark hover:bg-gray-800 text-white font-bold py-4 rounded-xl shadow-lg transition-transform active:scale-95">
                    Continue
                </button>
            </div>
        </div>
    </div>

    <script>
        // --- Fullscreen Handling ---
        function enterFullscreen() {
            const elem = document.documentElement;
            if (elem.requestFullscreen) {
                elem.requestFullscreen().catch(err => console.log(err));
            } else if (elem.webkitRequestFullscreen) { /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) { /* IE11 */
                elem.msRequestFullscreen();
            }
            
            // Hide overlay
            const overlay = document.getElementById('fullscreen-overlay');
            overlay.classList.add('opacity-0', 'pointer-events-none');
            setTimeout(() => overlay.remove(), 500);
            
            // Focus input
            focusInput();
        }

        // Auto-hide overlay if already fullscreen (e.g. launched from PWA)
        if (window.matchMedia('(display-mode: fullscreen)').matches || window.navigator.standalone) {
             document.getElementById('fullscreen-overlay').style.display = 'none';
        }

        // --- Clock & Date ---
        function updateTime() {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            document.getElementById('date').textContent = now.toLocaleDateString([], { weekday: 'long', month: 'long', day: 'numeric' });
        }
        setInterval(updateTime, 1000);
        updateTime();

        // --- Mode Switching ---
        let currentMode = 'qr';
        const manualInput = document.getElementById('manual-input');
        const toggleBg = document.getElementById('toggle-bg');
        const btnQr = document.getElementById('btn-qr');
        const btnNfc = document.getElementById('btn-nfc');
        const qrView = document.getElementById('qr-view');
        const nfcView = document.getElementById('nfc-view');

        function setMode(mode) {
            currentMode = mode;
            focusInput();
            
            if (mode === 'qr') {
                // UI State
                toggleBg.style.transform = 'translateX(0%)';
                btnQr.classList.replace('text-gray-500', 'text-dark');
                btnNfc.classList.replace('text-dark', 'text-gray-500');
                
                // Views
                qrView.classList.replace('opacity-0', 'opacity-100');
                qrView.classList.remove('pointer-events-none');
                
                nfcView.classList.replace('opacity-100', 'opacity-0');
                nfcView.classList.add('pointer-events-none');
                
                document.getElementById('status-text').textContent = "Scanner Ready";
                document.getElementById('status-dot').className = "w-3 h-3 bg-emerald-500 rounded-full animate-pulse";
                
            } else {
                // UI State
                toggleBg.style.transform = 'translateX(100%)'; 
                
                btnNfc.classList.replace('text-gray-500', 'text-dark');
                btnQr.classList.replace('text-dark', 'text-gray-500');
                
                // Views
                qrView.classList.replace('opacity-100', 'opacity-0');
                qrView.classList.add('pointer-events-none');

                nfcView.classList.replace('opacity-0', 'opacity-100');
                nfcView.classList.remove('pointer-events-none');
                
                document.getElementById('status-text').textContent = "Waiting for ID Card...";
                document.getElementById('status-dot').className = "w-3 h-3 bg-blue-500 rounded-full animate-pulse";
            }
        }
        
        function focusInput() {
            // Keep focus on the hidden input to catch scanner data
            if (!document.getElementById('result-modal').classList.contains('hidden')) return;
            manualInput.focus();
        }

        // Auto-focus logic
        document.addEventListener('click', focusInput);
        manualInput.addEventListener('blur', () => {
             // Optional: Force focus back after a slight delay if not in modal
             setTimeout(focusInput, 100);
        });

        // --- Stats ---
        let isScanning = false;

        function fetchStats() {
             fetch('{{ route("kiosk.stats") }}')
                .then(response => response.json())
                .then(data => {
                    const activeEl = document.getElementById('active-count');
                    const ongoingEl = document.getElementById('ongoing-count');
                    
                    if(activeEl) activeEl.textContent = data.active_count;
                    if(ongoingEl) ongoingEl.textContent = data.ongoing_count;
                })
                .catch(err => console.error("Stats Error:", err));
        }
        
        // Initial Fetch
        fetchStats();
        // Poll every 5 seconds
        setInterval(fetchStats, 5000);

        // --- Handling ---
        function handleScan(code) {
            isScanning = true;
            console.log("Scanned:", code);
            
            // Audio Feedback
            // const audio = new Audio('{{ asset("sounds/beep.mp3") }}');
            // audio.play().catch(e => {});

            // Fetch Data
            fetch(`/debug-user/${code}`)
                .then(response => response.json())
                .then(user => {
                    if (user && user.id) {
                        // Refresh stats immediately after a scan (though scan doesn't log yet, but for future proofing)
                        setTimeout(fetchStats, 1000);
                        showResult(true, user);
                    } else {
                        showResult(false, { name: "Unknown User", id_number: code });
                    }
                })
                .catch(err => {
                    showResult(false, { name: "Error", id_number: err.message });
                });
        }

        // --- Manual Input ---
        manualInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                const code = this.value;
                this.value = '';
                if (code.trim().length > 0) handleScan(code);
            }
        });

        // --- Modal ---
        function showResult(success, data) {
            const modal = document.getElementById('result-modal');
            const photo = document.getElementById('modal-photo');
            const name = document.getElementById('modal-name');
            const id = document.getElementById('modal-id');
            const title = document.getElementById('modal-title');
            const icon = document.getElementById('modal-icon');
            const message = document.getElementById('modal-message');

            modal.classList.remove('hidden');

            if (success) {
                title.textContent = "Success";
                title.className = "font-bold text-emerald-600 uppercase tracking-wide text-sm";
                icon.className = "fas fa-check-circle text-emerald-500";
                name.textContent = data.name;
                id.textContent = `ID: ${data.id_number}`;
                message.textContent = "Successfully Logged In";
                
                if (data.photo) {
                    photo.src = `/storage/${data.photo}`;
                } else {
                    photo.src = `https://ui-avatars.com/api/?name=${encodeURIComponent(data.name)}&background=random&size=128`;
                }
            } else {
                title.textContent = "Failed";
                title.className = "font-bold text-red-600 uppercase tracking-wide text-sm";
                icon.className = "fas fa-times-circle text-red-500";
                name.textContent = "Unknown ID";
                id.textContent = `Code: ${data.id_number}`;
                message.textContent = "User not found in database";
                photo.src = "https://ui-avatars.com/api/?name=Unknown&background=EF4444&color=fff";
            }
        }

        function closeModal() {
            document.getElementById('result-modal').classList.add('hidden');
            isScanning = false;
            focusInput();
        }

        // Init
        focusInput();
    </script>
</body>
</html>
