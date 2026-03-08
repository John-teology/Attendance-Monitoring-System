package com.example.libraryattendance.ui

import android.Manifest
import android.graphics.Color
import androidx.palette.graphics.Palette
import android.graphics.Bitmap
import android.graphics.drawable.BitmapDrawable
import android.util.Log
import android.app.PendingIntent
import android.content.Intent
import android.content.IntentFilter
import java.text.SimpleDateFormat
import java.util.Date
import java.util.Locale
import android.os.Handler
import android.os.Looper
import android.media.ToneGenerator
import android.media.AudioManager
import android.view.animation.AnimationUtils
import android.animation.AnimatorSet
import android.animation.ObjectAnimator
import android.view.KeyEvent
import android.view.View
import android.view.animation.Animation
import android.view.animation.LinearInterpolator
import android.widget.Button
import android.widget.EditText
import android.widget.ImageButton
import android.widget.ImageView
import android.widget.TextView
import android.widget.Toast
import androidx.activity.viewModels
import androidx.appcompat.app.AlertDialog
import androidx.appcompat.app.AppCompatActivity
import androidx.core.content.ContextCompat
import coil.load
import com.example.libraryattendance.LibraryApplication
import com.example.libraryattendance.R
import com.example.libraryattendance.data.local.User
import android.nfc.NfcAdapter
import android.nfc.Tag
import android.nfc.NdefMessage
import android.nfc.NdefRecord
import android.os.Build
import android.os.Bundle
import android.speech.tts.TextToSpeech
import android.text.InputType
import android.text.Spannable
import android.text.SpannableString
import android.text.style.StyleSpan
import android.graphics.Typeface

class MainActivity : AppCompatActivity(), TextToSpeech.OnInitListener {

    private var tts: TextToSpeech? = null
    private var isTtsReady = false
    private var toneGen: ToneGenerator? = null

    private val viewModel: MainViewModel by viewModels {
        MainViewModelFactory((application as LibraryApplication).repository)
    }

    private var nfcAdapter: NfcAdapter? = null
    
    // Scanner input buffer
    private val scanBuffer = StringBuilder()
    private val SCAN_TIMEOUT = 500L // Reset buffer if no input for 500ms
    private var lastKeyTime = 0L
    
    // UI Mode (QR or NFC)
    private var scanLineAnimator: ObjectAnimator? = null
    private var nfcAnimator: android.animation.AnimatorSet? = null
    private val activeAnimators = mutableListOf<AnimatorSet>()
    private var isQrMode = true
    private var isProcessingScan = false
    private var hasSyncedWithServer = false

    // Secret Settings Tap Counter
    private var settingsTapCount = 0
    private var firstSettingsTapTime = 0L
    
    // Date Time Updater
    private val handler = Handler(Looper.getMainLooper())
    private val timeRunnable = object : Runnable {
        override fun run() {
            updateDateTime()
            handler.postDelayed(this, 1000)
        }
    }

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        
        // --- FORCE DATABASE RESET V12 (One Time) ---
        // This ensures that the user's request to "remove all schema" is honored on first run of this version
        val sharedPrefs = getSharedPreferences("app_prefs", MODE_PRIVATE)
        val hasResetV12 = sharedPrefs.getBoolean("has_reset_db_v12", false)
        
        if (!hasResetV12) {
            val dbPath = getDatabasePath("library_attendance_db")
            if (dbPath.exists()) {
                dbPath.delete()
                // Also delete WAL/SHM
                java.io.File(dbPath.path + "-wal").delete()
                java.io.File(dbPath.path + "-shm").delete()
            }
            sharedPrefs.edit().putBoolean("has_reset_db_v12", true).apply()
            Toast.makeText(this, "System Update: Database Reset for Schema Fix (v12)", Toast.LENGTH_LONG).show()
        }
        // -------------------------------------------

        setContentView(R.layout.activity_main)

        // Initialize ToneGenerator and TextToSpeech
        try {
            toneGen = ToneGenerator(AudioManager.STREAM_MUSIC, 100)
        } catch (e: Exception) {
            Log.e("Audio", "Failed to init ToneGenerator", e)
        }

        try {
            // Check for Google TTS engine first
            if (isGoogleTtsInstalled(this)) {
                try {
                    tts = TextToSpeech(applicationContext, this, "com.google.android.tts")
                } catch (e: Exception) {
                    Log.w("TTS", "Failed to init Google TTS, falling back to default", e)
                    tts = TextToSpeech(applicationContext, this)
                }
            } else {
                tts = TextToSpeech(applicationContext, this)
            }
        } catch (e: Exception) {
            Log.e("TTS", "Failed to instantiate TTS", e)
        }


        nfcAdapter = NfcAdapter.getDefaultAdapter(this)
        
        // Start Clock
        handler.post(timeRunnable)
        
        // Initialize Mode (Default to QR)
        setScanMode(true)
        
        // Restore Sync State and enforce footer visibility immediately
        val prefs = getSharedPreferences("app_prefs", MODE_PRIVATE)
        hasSyncedWithServer = prefs.getBoolean("has_synced_with_server", false)
        
        val tvContact = findViewById<TextView>(R.id.tvContactFooter)
        if (hasSyncedWithServer) {
            tvContact.visibility = View.GONE
        }
        
        if (nfcAdapter == null) {
            Toast.makeText(this, "NFC is not supported on this device.", Toast.LENGTH_LONG).show()
        }
        
        viewModel.scanResult.observe(this) { result ->
            when (result) {
                is ScanResult.Success -> {
                    showSuccessDialog(result.user, result.entryType, result.isOffline)
                }
                is ScanResult.Error -> {
                    showErrorDialog(result.message)
                }
            }
        }
        
        // Stats Observers
        val tvTodayCount = findViewById<TextView>(R.id.tvTodayCount)
        val tvSyncedCount = findViewById<TextView>(R.id.tvSyncedCount)

        val btnModeQr = findViewById<View>(R.id.btnModeQr)
        val btnModeNfc = findViewById<View>(R.id.btnModeNfc)
        
        // Request focus on root container to prevent focus on clickable elements
        val toggleContainer = findViewById<View>(R.id.toggleContainer)
        toggleContainer.requestFocus()

        btnModeQr.setOnClickListener {
            if (isProcessingScan) return@setOnClickListener
            setScanMode(true)
            toggleContainer.requestFocus()
        }
        
        btnModeNfc.setOnClickListener {
            if (isProcessingScan) return@setOnClickListener
            setScanMode(false)
            toggleContainer.requestFocus()
        }

        viewModel.syncResult.observe(this) { result ->
            Toast.makeText(this, "Sync Status: $result", Toast.LENGTH_SHORT).show()
            // Assume any sync result that isn't an error implies connection
            if (!result.contains("Fail", ignoreCase = true) && !result.contains("Error", ignoreCase = true)) {
                hasSyncedWithServer = true
                getSharedPreferences("app_prefs", MODE_PRIVATE)
                    .edit()
                    .putBoolean("has_synced_with_server", true)
                    .apply()
                findViewById<TextView>(R.id.tvContactFooter).visibility = View.GONE
            }
        }

        // Observe School Name & Logo
        viewModel.schoolName.observe(this) { name ->
            val tvSchoolName = findViewById<TextView>(R.id.tvSchoolName)
            val tvContact = findViewById<TextView>(R.id.tvContactFooter)
            val defaultTitle = "Library Attendance"
            
            // Always set text, default or custom
            if (name.isNullOrBlank()) {
                tvSchoolName.text = defaultTitle
            } else {
                tvSchoolName.text = name
            }
            
            // Visibility logic for contact footer
            if (name.isNullOrBlank() || name.equals(defaultTitle, ignoreCase = true)) {
                 // Only show if we haven't synced
                 if (!hasSyncedWithServer) {
                    tvContact.visibility = View.VISIBLE
                 } else {
                    tvContact.visibility = View.GONE
                 }
            } else {
                 tvContact.visibility = View.GONE
                 // If we have a custom name, we definitely synced
                 hasSyncedWithServer = true
                 getSharedPreferences("app_prefs", MODE_PRIVATE)
                     .edit()
                     .putBoolean("has_synced_with_server", true)
                     .apply()
            }
            
            // Force Visible in case it was hidden
            tvSchoolName.visibility = View.VISIBLE
            
            // Secret Settings Trigger
            tvSchoolName.setOnClickListener {
                val currentTime = System.currentTimeMillis()
                
                if (settingsTapCount == 0) {
                    firstSettingsTapTime = currentTime
                    settingsTapCount++
                } else {
                    if (currentTime - firstSettingsTapTime > 2000) {
                        settingsTapCount = 1
                        firstSettingsTapTime = currentTime
                    } else {
                        settingsTapCount++
                    }
                }
                
                if (settingsTapCount >= 5) {
                    settingsTapCount = 0
                    showPinDialog()
                }
            }
        }

        viewModel.schoolLogoUrl.observe(this) { url ->
            val ivLogo = findViewById<ImageView>(R.id.ivLogo)
            if (!url.isNullOrEmpty()) {
                ivLogo.load(url) {
                    crossfade(true)
                    placeholder(R.drawable.ic_book)
                    error(R.drawable.ic_book)
                }
            } else {
                ivLogo.setImageResource(R.drawable.ic_book)
            }
        }

        viewModel.appBackgroundImage.observe(this) { url ->
            val ivAppBackground = findViewById<ImageView>(R.id.ivAppBackground)
            val mainCard = findViewById<androidx.cardview.widget.CardView>(R.id.mainCard)
            val bodyColor = viewModel.bodyBgColor.value
            val transparency = viewModel.cardTransparency.value ?: 80 // Default 80% opacity
            
            // Calculate alpha int (0-255) from percentage (0-100)
            // 100% -> 255 (Solid), 0% -> 0 (Transparent)
            val alphaInt = (transparency * 255) / 100
            
            if (!url.isNullOrEmpty()) {
                ivAppBackground.visibility = View.VISIBLE
                ivAppBackground.alpha = 0.8f
                ivAppBackground.load(url) {
                    crossfade(true)
                }
                
                // If body color is set, use it with calculated transparency
                if (!bodyColor.isNullOrEmpty()) {
                    try {
                        val parsedColor = android.graphics.Color.parseColor(bodyColor)
                        val transparentColor = androidx.core.graphics.ColorUtils.setAlphaComponent(parsedColor, alphaInt)
                        mainCard.setCardBackgroundColor(transparentColor)
                    } catch (e: Exception) {
                         // Fallback to white with calculated transparency
                        val whiteTransparent = androidx.core.graphics.ColorUtils.setAlphaComponent(android.graphics.Color.WHITE, alphaInt)
                        mainCard.setCardBackgroundColor(whiteTransparent)
                    }
                } else {
                     // Default white with calculated transparency
                    val whiteTransparent = androidx.core.graphics.ColorUtils.setAlphaComponent(android.graphics.Color.WHITE, alphaInt)
                    mainCard.setCardBackgroundColor(whiteTransparent)
                }
                
                mainCard.cardElevation = 0f
            } else {
                ivAppBackground.visibility = View.GONE
                
                // If no image, we usually want solid, but let's respect the transparency slider if user wants it transparent against root bg
                // However, usually "Solid" means 100% when no image. But if root layout has color, transparency matters.
                
                if (!bodyColor.isNullOrEmpty()) {
                    try {
                        val parsedColor = android.graphics.Color.parseColor(bodyColor)
                        // Apply transparency even without BG image (shows root color behind it if any, or just blends)
                        // Actually, if solid color is set, rootLayout gets it too. So mainCard transparency just shows rootLayout color (same).
                        // So for "Solid" look, we just use the color.
                        
                        // BUT, if user wants transparency against a gradient root (if we kept gradient), it matters.
                        // Currently rootLayout gets bodyBgColor. So MainCard transparency doesn't do much if both are same color.
                        
                        mainCard.setCardBackgroundColor(parsedColor) // Keep solid for now when no image
                        findViewById<View>(R.id.rootLayout).setBackgroundColor(parsedColor)
                    } catch (e: Exception) {
                        mainCard.setCardBackgroundColor(android.graphics.Color.WHITE)
                    }
                } else {
                    mainCard.setCardBackgroundColor(android.graphics.Color.WHITE)
                }
                
                mainCard.cardElevation = 8f * resources.displayMetrics.density
            }
        }
        
        viewModel.cardTransparency.observe(this) { transparency ->
             // Re-trigger background update logic when transparency changes
             viewModel.appBackgroundImage.value?.let { 
                 // Force refresh by re-setting value or just relying on this observer to update manually?
                 // Easier to just duplicate the color logic or extract a function.
                 // For now, let's just trigger the main observer if possible, or copy logic.
                 
                 val mainCard = findViewById<androidx.cardview.widget.CardView>(R.id.mainCard)
                 val bodyColor = viewModel.bodyBgColor.value
                 val alphaInt = (transparency * 255) / 100
                 
                 // Only apply if we have a background image visible (which implies we need transparency)
                 // Or if we want transparency regardless.
                 val ivAppBackground = findViewById<ImageView>(R.id.ivAppBackground)
                 
                 if (ivAppBackground.visibility == View.VISIBLE) {
                     val baseColor = if (!bodyColor.isNullOrEmpty()) {
                         try { android.graphics.Color.parseColor(bodyColor) } catch (e: Exception) { android.graphics.Color.WHITE }
                     } else {
                         android.graphics.Color.WHITE
                     }
                     val transparentColor = androidx.core.graphics.ColorUtils.setAlphaComponent(baseColor, alphaInt)
                     mainCard.setCardBackgroundColor(transparentColor)
                 }
             }
        }

        // Customization Observers
        viewModel.schoolNameColor.observe(this) { color ->
            if (!color.isNullOrEmpty()) {
                try {
                    val parsedColor = android.graphics.Color.parseColor(color)
                    findViewById<TextView>(R.id.tvSchoolName).setTextColor(parsedColor)
                    
                    // Also update Status Footer and Date Time to match School Name Color
                    findViewById<TextView>(R.id.tvStatusFooter).setTextColor(parsedColor)
                    findViewById<TextView>(R.id.tvDateTime).setTextColor(parsedColor)
                } catch (e: Exception) {
                    Log.e("Customization", "Invalid school name color: $color")
                }
            }
        }

        viewModel.buttonBgColor.observe(this) { color ->
            updateButtons(color, viewModel.buttonTransparency.value ?: 100)
        }

        viewModel.buttonTransparency.observe(this) { transparency ->
             updateButtons(viewModel.buttonBgColor.value, transparency)
        }

        viewModel.iconColor.observe(this) { color ->
             // Re-apply scan mode to update icon colors immediately
             setScanMode(isQrMode)
        }

        viewModel.bodyBgColor.observe(this) { color ->
            if (!color.isNullOrEmpty()) {
                try {
                    val parsedColor = android.graphics.Color.parseColor(color)
                    val mainCard = findViewById<androidx.cardview.widget.CardView>(R.id.mainCard)
                    val ivAppBackground = findViewById<ImageView>(R.id.ivAppBackground)
                    
                    // Apply to Main Card (content area)
                    if (ivAppBackground.visibility == View.VISIBLE) {
                        // If BG image exists, apply transparency
                        val transparentColor = androidx.core.graphics.ColorUtils.setAlphaComponent(parsedColor, 204)
                        mainCard.setCardBackgroundColor(transparentColor)
                    } else {
                        // Solid color
                        mainCard.setCardBackgroundColor(parsedColor)
                        // Also set root background
                        findViewById<View>(R.id.rootLayout).setBackgroundColor(parsedColor)
                    }
                } catch (e: Exception) {
                    Log.e("Customization", "Invalid body color: $color")
                }
            }
        }

        viewModel.fontStyle.observe(this) { font ->
            val tvSchoolName = findViewById<TextView>(R.id.tvSchoolName)
            val typeface = when (font) {
                "Sans Serif" -> Typeface.SANS_SERIF
                "Serif" -> Typeface.SERIF
                "Monospace" -> Typeface.MONOSPACE
                "Cursive" -> Typeface.create("cursive", Typeface.NORMAL) // May fallback to Sans Serif on some Android versions
                "Casual" -> Typeface.create("casual", Typeface.NORMAL) // Android 5.0+
                else -> Typeface.DEFAULT_BOLD
            }
            tvSchoolName.typeface = typeface
        }

        viewModel.volumeLevel.observe(this) { level ->
            if (level != null) {
                setDeviceVolume(level)
            }
        }

        // Dashboard Observers
        viewModel.todayCount.observe(this) { count ->
            tvTodayCount.text = count.toString()
        }
        
        viewModel.ongoingCount.observe(this) { count ->
            tvSyncedCount.text = count.toString()
            
            // Also treat getting valid stats as a sync event if needed, 
            // but relying on syncResult/schoolName is safer to avoid hiding on offline cache load.
            // However, if we want to enforce the "gone once synced" rule strictly:
            if (hasSyncedWithServer) {
                 findViewById<TextView>(R.id.tvContactFooter).visibility = View.GONE
            }
        }
    }
    
    private fun updateButtons(color: String?, transparency: Int) {
        if (!color.isNullOrEmpty()) {
            try {
                val parsedColor = android.graphics.Color.parseColor(color)
                val alphaInt = (transparency * 255) / 100
                val transparentColor = androidx.core.graphics.ColorUtils.setAlphaComponent(parsedColor, alphaInt)
                
                // Create a ColorStateList that applies the custom color only when selected
                // and keeps it White (or default) when not selected.
                val states = arrayOf(
                    intArrayOf(android.R.attr.state_selected),
                    intArrayOf() // Default
                )
                
                // For default state (unselected), we usually want White with some transparency if button transparency is global
                // But usually transparency only applies to the colored background.
                // Let's assume unselected remains White (solid or slightly transparent if desired, but standard is white).
                
                val colors = intArrayOf(
                    transparentColor,
                    android.graphics.Color.WHITE
                )
                
                val colorStateList = android.content.res.ColorStateList(states, colors)
                
                findViewById<View>(R.id.btnModeQr).backgroundTintList = colorStateList
                findViewById<View>(R.id.btnModeNfc).backgroundTintList = colorStateList
                
            } catch (e: Exception) {
                Log.e("Customization", "Invalid button color logic: $color")
            }
        }
    }

    private fun updateDateTime() {
        val tvDateTime = findViewById<TextView>(R.id.tvDateTime)
        val sdf = SimpleDateFormat("EEEE, MMMM d, yyyy • h:mm a", Locale.getDefault())
        tvDateTime.text = sdf.format(Date())
    }

    private fun setScanMode(isQr: Boolean) {
        isQrMode = isQr
        val btnModeQr = findViewById<View>(R.id.btnModeQr)
        val btnModeNfc = findViewById<View>(R.id.btnModeNfc)
        val textQr = findViewById<TextView>(R.id.textQr)
        val iconQr = findViewById<ImageView>(R.id.iconQr)
        val textNfc = findViewById<TextView>(R.id.textNfc)
        val iconNfc = findViewById<ImageView>(R.id.iconNfc)

        val qrScanArea = findViewById<View>(R.id.qrScanArea)
        val nfcScanArea = findViewById<View>(R.id.nfcScanArea)
        val ivCenterIcon = findViewById<ImageView>(R.id.ivCenterIcon)
        val tvStatusHeader = findViewById<TextView>(R.id.tvStatusHeader)
        val tvStatusFooter = findViewById<TextView>(R.id.tvStatusFooter)

        // Determine Active Color from ViewModel or Default
        var activeColor = ContextCompat.getColor(this, android.R.color.holo_green_dark) // Default Green
        val customIconColor = viewModel.iconColor.value
        if (!customIconColor.isNullOrEmpty()) {
            try {
                activeColor = android.graphics.Color.parseColor(customIconColor)
            } catch (e: Exception) {
                Log.e("Customization", "Invalid icon color: $customIconColor")
            }
        }
        
        val inactiveColor = ContextCompat.getColor(this, android.R.color.darker_gray)

        // Cancel existing animations
        stopWaveAnimation()
        
        // Reset Wave Colors (Blue Defaults) - Or match icon color?
        // User asked for "QR and NFC Icon and text". 
        // Let's also update the wave color to match the active icon color for consistency if it's custom.
        val wave1 = findViewById<View>(R.id.wave1)
        val wave2 = findViewById<View>(R.id.wave2)
        val wave3 = findViewById<View>(R.id.wave3)
        
        // Make waves a lighter version of the active color?
        // Or keep them blue? The original code had blue defaults, but green for scanning.
        // Let's default to blueish, but later `triggerScanProcess` changes them to green.
        // We should probably update `triggerScanProcess` too.
        
        // For now, reset to blueish defaults or maybe just keep them as is until scan.
        wave1.backgroundTintList = android.content.res.ColorStateList.valueOf(android.graphics.Color.parseColor("#DBEAFE"))
        wave2.backgroundTintList = android.content.res.ColorStateList.valueOf(android.graphics.Color.parseColor("#BFDBFE"))
        wave3.backgroundTintList = android.content.res.ColorStateList.valueOf(android.graphics.Color.parseColor("#93C5FD"))
        
        if (isQr) {
            btnModeQr.isSelected = true
            btnModeNfc.isSelected = false
            
            // QR Active Style
            textQr.setTextColor(activeColor)
            iconQr.setColorFilter(activeColor)
            
            // NFC Inactive Style
            textNfc.setTextColor(inactiveColor)
            iconNfc.setColorFilter(inactiveColor)
            
            qrScanArea.visibility = View.VISIBLE
            nfcScanArea.visibility = View.GONE
            
            ivCenterIcon.setImageResource(R.drawable.ic_qr_code)
            ivCenterIcon.setColorFilter(activeColor)
            
            tvStatusHeader.text = ""
            tvStatusFooter.text = "Scanning Standby..."
            
            // Start QR Animation (Same wave effect)
            startWaveAnimation()
        } else {
            btnModeQr.isSelected = false
            btnModeNfc.isSelected = true
            
            // QR Inactive Style
            textQr.setTextColor(inactiveColor)
            iconQr.setColorFilter(inactiveColor)
            
            // NFC Active Style
            textNfc.setTextColor(activeColor)
            iconNfc.setColorFilter(activeColor)
            
            qrScanArea.visibility = View.GONE
            nfcScanArea.visibility = View.VISIBLE
            
            ivCenterIcon.setImageResource(R.drawable.ic_nfc)
            ivCenterIcon.setColorFilter(activeColor)
            
            tvStatusHeader.text = ""
            tvStatusFooter.text = "Ready for Tap..."
            
            // Start NFC Animation
            startWaveAnimation()
        }

        // Toggle NFC Hardware Mode based on selection
        if (lifecycle.currentState.isAtLeast(androidx.lifecycle.Lifecycle.State.RESUMED)) {
            if (isQr) {
                disableNfcForegroundDispatch()
            } else {
                enableNfcForegroundDispatch()
            }
        }
    }

    private fun stopWaveAnimation() {
        activeAnimators.forEach { it.cancel() }
        activeAnimators.clear()
        
        // Reset Views
        val wave1 = findViewById<View>(R.id.wave1)
        val wave2 = findViewById<View>(R.id.wave2)
        val wave3 = findViewById<View>(R.id.wave3)
        
        listOf(wave1, wave2, wave3).forEach {
            it.alpha = 0f
            it.scaleX = 1f
            it.scaleY = 1f
        }
        
        // Reset Center Icon
        val ivCenterIcon = findViewById<ImageView>(R.id.ivCenterIcon)
        ivCenterIcon.scaleX = 1f
        ivCenterIcon.scaleY = 1f
        
        // Reset Toggle Icons
        val iconQr = findViewById<ImageView>(R.id.iconQr)
        val iconNfc = findViewById<ImageView>(R.id.iconNfc)
        
        iconQr.scaleX = 1f
        iconQr.scaleY = 1f
        iconQr.alpha = 1f
        
        iconNfc.scaleX = 1f
        iconNfc.scaleY = 1f
        iconNfc.alpha = 1f
    }

    private fun startWaveAnimation() {
        val wave1 = findViewById<View>(R.id.wave1)
        val wave2 = findViewById<View>(R.id.wave2)
        val wave3 = findViewById<View>(R.id.wave3)
        
        // 1. Animate Waves
        listOf(wave1, wave2, wave3).forEachIndexed { index, view ->
            view.visibility = View.VISIBLE
            
            // Initial State
            view.alpha = 0f
            view.scaleX = 1f
            view.scaleY = 1f
            
            val duration = 4000L
            val delay = index * 1300L
            
            val scaleX = ObjectAnimator.ofFloat(view, "scaleX", 1f, 5f)
            scaleX.repeatCount = ObjectAnimator.INFINITE
            scaleX.duration = duration
            scaleX.startDelay = delay
            
            val scaleY = ObjectAnimator.ofFloat(view, "scaleY", 1f, 5f)
            scaleY.repeatCount = ObjectAnimator.INFINITE
            scaleY.duration = duration
            scaleY.startDelay = delay
            
            val alpha = ObjectAnimator.ofFloat(view, "alpha", 0.5f, 0f)
            alpha.repeatCount = ObjectAnimator.INFINITE
            alpha.duration = duration
            alpha.startDelay = delay
            
            val set = AnimatorSet()
            set.playTogether(scaleX, scaleY, alpha)
            set.start()
            
            activeAnimators.add(set)
        }
        
        // 2. Animate Center Icon (Pulse)
        val ivCenterIcon = findViewById<ImageView>(R.id.ivCenterIcon)
        val centerScaleX = ObjectAnimator.ofFloat(ivCenterIcon, "scaleX", 1f, 1.1f, 1f)
        centerScaleX.repeatCount = ObjectAnimator.INFINITE
        centerScaleX.duration = 2000
        
        val centerScaleY = ObjectAnimator.ofFloat(ivCenterIcon, "scaleY", 1f, 1.1f, 1f)
        centerScaleY.repeatCount = ObjectAnimator.INFINITE
        centerScaleY.duration = 2000
        
        val centerSet = AnimatorSet()
        centerSet.playTogether(centerScaleX, centerScaleY)
        centerSet.start()
        activeAnimators.add(centerSet)
        
        // 3. Animate Active Toggle Icon
        val activeIcon = if (isQrMode) findViewById<ImageView>(R.id.iconQr) else findViewById<ImageView>(R.id.iconNfc)
        
        // Pulse Effect for Toggle Icon
        val toggleScaleX = ObjectAnimator.ofFloat(activeIcon, "scaleX", 1f, 1.15f, 1f)
        toggleScaleX.repeatCount = ObjectAnimator.INFINITE
        toggleScaleX.duration = 1500
        
        val toggleScaleY = ObjectAnimator.ofFloat(activeIcon, "scaleY", 1f, 1.15f, 1f)
        toggleScaleY.repeatCount = ObjectAnimator.INFINITE
        toggleScaleY.duration = 1500
        
        val toggleSet = AnimatorSet()
        toggleSet.playTogether(toggleScaleX, toggleScaleY)
        toggleSet.start()
        activeAnimators.add(toggleSet)
    }

    // TTS Initialization
    override fun onInit(status: Int) {
        if (status == TextToSpeech.SUCCESS) {
            Log.d("TTS", "Initialization Success")
            
            // Try US English first, fall back to Default
            var result = tts?.setLanguage(Locale.US)
            
            // Set Pitch and Rate as requested
            tts?.setSpeechRate(0.9f) // clearer in public areas
            tts?.setPitch(1.0f)

            if (result == TextToSpeech.LANG_MISSING_DATA || result == TextToSpeech.LANG_NOT_SUPPORTED) {
                Log.w("TTS", "US English not supported, trying device default")
                result = tts?.setLanguage(Locale.getDefault())
            }
            
            if (result == TextToSpeech.LANG_MISSING_DATA || result == TextToSpeech.LANG_NOT_SUPPORTED) {
                 Log.e("TTS", "Language not supported")
                 // Toast.makeText(this, "TTS Error: Language not supported", Toast.LENGTH_LONG).show()
            } else {
                 isTtsReady = true
                 Log.d("TTS", "TTS Ready and Configured")
                 // Configure Audio Attributes for Media/Music (Standard Volume)
                 if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                    val attributes = android.media.AudioAttributes.Builder()
                        .setUsage(android.media.AudioAttributes.USAGE_MEDIA)
                        .setContentType(android.media.AudioAttributes.CONTENT_TYPE_SPEECH)
                        .build()
                    tts?.setAudioAttributes(attributes)
                }
            }
        } else {
            Log.e("TTS", "Initialization Failed with status: $status")
            // Try to recover by re-initializing with default engine if we were trying Google
            // But we can't easily switch engines inside onInit callback for the same instance.
            // Just rely on ToneGenerator fallback.
            // Suppress the toast to avoid annoying user since we have beep fallback now.
            // Toast.makeText(this, "TTS Init Failed! Code: $status", Toast.LENGTH_LONG).show()
        }
    }

    private fun speak(message: String) {
        if (tts != null) {
            // Check if initialized
            if (!isTtsReady) {
                Log.w("TTS", "TTS not ready yet, skipping message: $message")
                return
            }

            // Ensure Volume is Audible before speaking - DISABLED per request
            // ensureVolumeIsAudible()
            
            Log.d("TTS", "Attempting to speak: $message")
            
            val params = Bundle()
            params.putFloat(TextToSpeech.Engine.KEY_PARAM_VOLUME, 1.0f)
            params.putInt(TextToSpeech.Engine.KEY_PARAM_STREAM, AudioManager.STREAM_MUSIC)
            
            if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.LOLLIPOP) {
                val result = tts?.speak(message, TextToSpeech.QUEUE_FLUSH, params, "LIBRARY_TTS")
                if (result == TextToSpeech.ERROR) {
                    Log.e("TTS", "Error in speaking (Lollipop+)")
                }
            } else {
                val hashMap = java.util.HashMap<String, String>()
                hashMap[TextToSpeech.Engine.KEY_PARAM_VOLUME] = "1.0"
                hashMap[TextToSpeech.Engine.KEY_PARAM_STREAM] = AudioManager.STREAM_MUSIC.toString()
                val result = tts?.speak(message, TextToSpeech.QUEUE_FLUSH, hashMap)
                 if (result == TextToSpeech.ERROR) {
                    Log.e("TTS", "Error in speaking (Legacy)")
                }
            }
        } else {
            Log.e("TTS", "TTS Engine is null")
        }
    }
    
    // Handle External Scanner Input (Keyboard Mode)
    override fun dispatchKeyEvent(event: KeyEvent): Boolean {
        // Only allow keyboard input (QR scanner) if in QR Mode
        if (!isQrMode) {
            return super.dispatchKeyEvent(event)
        }
        
        // Allow Back button and other system keys to propagate
        // This ensures we can still exit the app or adjust volume
        val keyCode = event.keyCode
        if (keyCode == KeyEvent.KEYCODE_BACK || 
            keyCode == KeyEvent.KEYCODE_VOLUME_UP || 
            keyCode == KeyEvent.KEYCODE_VOLUME_DOWN ||
            keyCode == KeyEvent.KEYCODE_POWER ||
            keyCode == KeyEvent.KEYCODE_HOME) { 
            return super.dispatchKeyEvent(event)
        }

        if (event.action == KeyEvent.ACTION_UP) {
            val currentTime = System.currentTimeMillis()
            
            // Reset buffer if timeout occurred
            if (currentTime - lastKeyTime > SCAN_TIMEOUT) {
                scanBuffer.clear()
            }
            lastKeyTime = currentTime

            val char = event.unicodeChar.toChar()
            
            // Enter key or Newline usually marks end of scan
            if (event.keyCode == KeyEvent.KEYCODE_ENTER || char == '\n') {
                if (scanBuffer.isNotEmpty()) {
                    val scannedCode = scanBuffer.toString().trim()
                    if (scannedCode.isNotEmpty()) {
                        triggerScanProcess(scannedCode, "QR")
                    }
                    scanBuffer.clear()
                    // return true // Implicitly returning true at end
                }
            } else if (!Character.isISOControl(char)) {
                scanBuffer.append(char)
            }
        }
        
        // Consume ALL other key events (DOWN and UP) to prevent UI focus changes
        return true
    }

    private fun showPinDialog() {
        val builder = AlertDialog.Builder(this)
        builder.setTitle("Admin Access Required")
        builder.setMessage("Enter Admin PIN")

        val input = EditText(this)
        input.inputType = InputType.TYPE_CLASS_NUMBER or InputType.TYPE_NUMBER_VARIATION_PASSWORD
        builder.setView(input)

        builder.setPositiveButton("Unlock") { _, _ ->
            val pin = input.text.toString()
            if (viewModel.checkPin(pin)) {
                showSettingsDialog()
            } else {
                Toast.makeText(this, "Invalid PIN", Toast.LENGTH_SHORT).show()
            }
        }
        builder.setNegativeButton("Cancel") { dialog, _ ->
            dialog.cancel()
        }
        builder.show()
    }

    private fun showSettingsDialog() {
        val builder = AlertDialog.Builder(this)
        builder.setTitle("Settings")

        // Create a layout for the dialog to hold multiple inputs if needed, or just IP for now
        val layout = android.widget.LinearLayout(this)
        layout.orientation = android.widget.LinearLayout.VERTICAL
        layout.setPadding(50, 40, 50, 10)

        val ipLabel = TextView(this)
        ipLabel.text = "Server Address (IP):"
        layout.addView(ipLabel)

        val ipInput = EditText(this)
        ipInput.inputType = InputType.TYPE_CLASS_TEXT or InputType.TYPE_TEXT_VARIATION_URI
        ipInput.setText(viewModel.getServerIp())
        layout.addView(ipInput)

        val portLabel = TextView(this)
        portLabel.text = "Server Port (Default: 8000):"
        layout.addView(portLabel)

        val portInput = EditText(this)
        portInput.inputType = InputType.TYPE_CLASS_NUMBER
        portInput.setText(viewModel.getServerPort())
        layout.addView(portInput)

        // Option to change PIN could be added here in a real app

        builder.setView(layout)

        builder.setPositiveButton("Save") { _, _ ->
            val ip = ipInput.text.toString()
            val port = portInput.text.toString()
            
            // Basic validation
            if (ip.isNotBlank()) {
                viewModel.saveServerIp(ip)
                // Save port if provided, otherwise default to 8000 is handled in repo, 
                // but better to save what user sees.
                if (port.isNotBlank()) {
                    viewModel.saveServerPort(port)
                } else {
                    viewModel.saveServerPort("8000")
                }
                
                Toast.makeText(this, "Settings Saved", Toast.LENGTH_SHORT).show()
            } else {
                Toast.makeText(this, "Invalid Address", Toast.LENGTH_SHORT).show()
            }
        }
        builder.setNegativeButton("Cancel") { dialog, _ ->
            dialog.cancel()
        }

        builder.show()
    }

    override fun onResume() {
        super.onResume()
        if (!isQrMode) {
            enableNfcForegroundDispatch()
        }
    }

    override fun onPause() {
        super.onPause()
        disableNfcForegroundDispatch()
    }

    override fun onDestroy() {
        super.onDestroy()
        if (tts != null) {
            tts?.stop()
            tts?.shutdown()
        }
        toneGen?.release()
    }
    
    // NFC Handling
    private fun enableNfcForegroundDispatch() {
        nfcAdapter?.let { adapter ->
            if (adapter.isEnabled) {
                val intent = Intent(this, javaClass).addFlags(Intent.FLAG_ACTIVITY_SINGLE_TOP)
                val pendingIntent = PendingIntent.getActivity(
                    this, 0, intent,
                    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.S) PendingIntent.FLAG_MUTABLE else 0
                )
                val filters = arrayOf(
                    IntentFilter(NfcAdapter.ACTION_NDEF_DISCOVERED),
                    IntentFilter(NfcAdapter.ACTION_TAG_DISCOVERED),
                    IntentFilter(NfcAdapter.ACTION_TECH_DISCOVERED)
                )
                adapter.enableForegroundDispatch(this, pendingIntent, filters, null)
            }
        }
    }

    private fun disableNfcForegroundDispatch() {
        nfcAdapter?.disableForegroundDispatch(this)
    }

    override fun onNewIntent(intent: Intent) { // Deprecated in Java but valid in Kotlin override for Activity
        super.onNewIntent(intent)
        
        if (NfcAdapter.ACTION_TAG_DISCOVERED == intent.action || 
            NfcAdapter.ACTION_NDEF_DISCOVERED == intent.action || 
            NfcAdapter.ACTION_TECH_DISCOVERED == intent.action) {
            
            var scannedValue: String? = null

            // Try to read NDEF message
            val rawMsgs = intent.getParcelableArrayExtra(NfcAdapter.EXTRA_NDEF_MESSAGES)
            if (rawMsgs != null && rawMsgs.isNotEmpty()) {
                val ndefMessage = rawMsgs[0] as NdefMessage
                val record = ndefMessage.records.firstOrNull()
                
                record?.let {
                    // Check if it's a text record
                    if (it.tnf == NdefRecord.TNF_WELL_KNOWN && java.util.Arrays.equals(it.type, NdefRecord.RTD_TEXT)) {
                        try {
                            val payload = it.payload
                            val textEncoding = if ((payload[0].toInt() and 128) == 0) "UTF-8" else "UTF-16"
                            val languageCodeLength = payload[0].toInt() and 51
                            scannedValue = String(
                                payload, 
                                languageCodeLength + 1, 
                                payload.size - languageCodeLength - 1, 
                                java.nio.charset.Charset.forName(textEncoding)
                            )
                        } catch (e: Exception) {
                            Log.e("NFC", "Error parsing NDEF text record", e)
                        }
                    }
                }
            }

            // If NDEF read successful, use it. Otherwise, fallback to UID.
            if (scannedValue != null) {
                handleRfid(scannedValue!!)
            } else {
                val tag: Tag? = if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
                    intent.getParcelableExtra(NfcAdapter.EXTRA_TAG, Tag::class.java)
                } else {
                    @Suppress("DEPRECATION")
                    intent.getParcelableExtra(NfcAdapter.EXTRA_TAG)
                }
                
                tag?.let {
                    val uid = it.id.joinToString("") { byte -> "%02x".format(byte) }
                    handleRfid(uid)
                }
            }
        }
    }

    private fun handleRfid(uid: String) {
        triggerScanProcess(uid, "RFID")
    }

    private fun triggerScanProcess(code: String, type: String) {
        if (isProcessingScan) return
        isProcessingScan = true
        
        // Ensure footer stays hidden during scan process if we have synced
        if (hasSyncedWithServer) {
            findViewById<TextView>(R.id.tvStatusFooter)?.let { footer ->
                // This is the STATUS footer, not contact. 
                // But let's check Contact Footer explicitly here too just in case.
                findViewById<TextView>(R.id.tvContactFooter)?.visibility = View.GONE
            }
        }

        val tvStatusFooter = findViewById<TextView>(R.id.tvStatusFooter)
        val wave1 = findViewById<View>(R.id.wave1)
        val wave2 = findViewById<View>(R.id.wave2)
        val wave3 = findViewById<View>(R.id.wave3)

        // Update Text
        if (type == "QR") {
            tvStatusFooter.text = "Scanning Your QR"
        } else {
            tvStatusFooter.text = "Scanning Your ID"
        }

        // Change Wave to Custom Active Color or Green (Emerald 500)
        var activeColor = ContextCompat.getColor(this, android.R.color.holo_green_dark)
        val customIconColor = viewModel.iconColor.value
        if (!customIconColor.isNullOrEmpty()) {
            try {
                activeColor = android.graphics.Color.parseColor(customIconColor)
            } catch (e: Exception) { }
        } else {
             activeColor = android.graphics.Color.parseColor("#10B981")
        }

        val colorState = android.content.res.ColorStateList.valueOf(activeColor)
        wave1.backgroundTintList = colorState
        wave2.backgroundTintList = colorState
        wave3.backgroundTintList = colorState

        // Delay 2 seconds
        handler.postDelayed({
            viewModel.processScan(code, type)
            isProcessingScan = false
            setScanMode(isQrMode)
        }, 2000)
    }

    private fun playSound(isSuccess: Boolean) {
        try {
            if (isSuccess) {
                toneGen?.startTone(ToneGenerator.TONE_PROP_BEEP, 150)
            } else {
                // Double beep for error
                toneGen?.startTone(ToneGenerator.TONE_CDMA_ALERT_CALL_GUARD, 200)
            }
        } catch (e: Exception) {
            Log.e("Tone", "Failed to play tone", e)
        }
    }

    private fun showSuccessDialog(user: User, entryType: String?, isOffline: Boolean = false) {
        val dialogView = layoutInflater.inflate(R.layout.dialog_scan_result, null)
        val builder = AlertDialog.Builder(this).setView(dialogView)
        val dialog = builder.create()
        dialog.window?.setBackgroundDrawableResource(android.R.color.transparent)

        val ivStatus = dialogView.findViewById<ImageView>(R.id.ivStatus)
        val tvTitle = dialogView.findViewById<TextView>(R.id.tvTitle)
        val tvMessage = dialogView.findViewById<TextView>(R.id.tvMessage)
        val btnClose = dialogView.findViewById<Button>(R.id.btnClose)

        val tvUserName = dialogView.findViewById<TextView>(R.id.tvUserName)
        val tvIdNumber = dialogView.findViewById<TextView>(R.id.tvIdNumber)
        val tvUserDetails = dialogView.findViewById<TextView>(R.id.tvUserDetails)
        val ivProfile = dialogView.findViewById<ImageView>(R.id.ivProfile)

        ivStatus.setImageResource(R.drawable.ic_check_circle)
        ivStatus.setColorFilter(ContextCompat.getColor(this, android.R.color.holo_green_dark))
        // Set Title based on Entry Type
        if (entryType == "IN") {
            tvTitle.text = "Welcome"
        } else {
            tvTitle.text = "Thank you for Visiting"
        }
        tvTitle.setTextColor(ContextCompat.getColor(this, android.R.color.holo_green_dark))
        
        val sdf = SimpleDateFormat("h:mm a", Locale.getDefault())
        val timeString = sdf.format(Date())
        
        val typeText = if (entryType == "OUT") "timed out" else "timed in"
        
        // Populate User Details
        tvUserName.text = user.fullName
        tvIdNumber.text = "ID: ${user.idNumber}"
        
        var details = ""
        if (user.userType.equals("student", ignoreCase = true)) {
            if (!user.course.isNullOrEmpty()) details += user.course + "\n"
            if (!user.yearLevel.isNullOrEmpty()) details += user.yearLevel + "\n"
        } else {
            if (!user.designation.isNullOrEmpty()) details += user.designation + "\n"
        }
        if (!user.department.isNullOrEmpty()) details += user.department
        
        tvUserDetails.text = details.trim()

        // Profile Picture
        if (!user.profilePicture.isNullOrEmpty()) {
            val serverIp = viewModel.getServerIp()
            val serverPort = viewModel.getServerPort()
            
            val imageUrl = if (serverIp.startsWith("http")) {
                "$serverIp/storage/${user.profilePicture}"
            } else {
                "http://$serverIp:$serverPort/storage/${user.profilePicture}"
            }
            
            // Log the URL for debugging
            Log.d("ProfilePic", "Loading image from: $imageUrl")
            
            ivProfile.load(imageUrl) {
                crossfade(false) // Disable crossfade to show image instantly
                placeholder(R.drawable.ic_user)
                error(R.drawable.ic_user)
                listener(
                    onError = { _, result -> Log.e("ProfilePic", "Error loading image: ${result.throwable.message}") },
                    onSuccess = { _, _ -> Log.d("ProfilePic", "Image loaded successfully") }
                )
            }
        } else {
            Log.d("ProfilePic", "No profile picture set for user ${user.fullName}")
            ivProfile.setImageResource(R.drawable.ic_user)
        }
        
        // Simple Message (No Name)
        tvMessage.text = "You have successfully $typeText at $timeString"
        
        // Text to Speech
        if (entryType == "IN") {
            speak("Welcome. Time in.")
        } else {
            speak("Thanks for visiting. Time out.")
        }
        
        // Also play success beep
        playSound(true)
        
        btnClose.setBackgroundColor(ContextCompat.getColor(this, android.R.color.holo_green_dark))
        btnClose.setOnClickListener { dialog.dismiss() }

        dialog.show()
        
        // Auto dismiss after 5 seconds
        dialogView.postDelayed({ 
            if (dialog.isShowing) {
                try { dialog.dismiss() } catch(e: Exception) {} 
            }
        }, 5000)
    }

    private fun showErrorDialog(message: String) {
        val dialogView = layoutInflater.inflate(R.layout.dialog_scan_result, null)
        val builder = AlertDialog.Builder(this).setView(dialogView)
        val dialog = builder.create()
        dialog.window?.setBackgroundDrawableResource(android.R.color.transparent)

        val ivStatus = dialogView.findViewById<ImageView>(R.id.ivStatus)
        val tvTitle = dialogView.findViewById<TextView>(R.id.tvTitle)
        val tvMessage = dialogView.findViewById<TextView>(R.id.tvMessage)
        val btnClose = dialogView.findViewById<Button>(R.id.btnClose)
        
        // Hide User Details in Error Dialog
        dialogView.findViewById<View>(R.id.ivProfile).visibility = View.GONE
        (dialogView.findViewById<View>(R.id.ivProfile).parent as? View)?.visibility = View.GONE
        dialogView.findViewById<View>(R.id.tvUserName).visibility = View.GONE
        dialogView.findViewById<View>(R.id.tvUserDetails).visibility = View.GONE

        ivStatus.setImageResource(R.drawable.ic_error)
        ivStatus.setColorFilter(ContextCompat.getColor(this, android.R.color.holo_red_dark))
        tvTitle.text = "Access Denied" // Default title
        tvTitle.setTextColor(ContextCompat.getColor(this, android.R.color.holo_red_dark))
        
        // Error Beep and Notification
        speak("Invalid entry.")
        playSound(false)

        // Custom messages based on backend response
        if (message.contains("Too fast")) {
             tvTitle.text = "Already Recorded"
             tvTitle.setTextColor(ContextCompat.getColor(this, android.R.color.holo_orange_dark))
             // Use generic error icon since clock might be missing
             ivStatus.setImageResource(R.drawable.ic_error) 
             ivStatus.setColorFilter(ContextCompat.getColor(this, android.R.color.holo_orange_dark))
             
             tvMessage.text = message.replace("Too fast! ", "")
             
             btnClose.setBackgroundColor(ContextCompat.getColor(this, android.R.color.holo_orange_dark))
        } else if (message.contains("invalid schema") || message.contains("Pre-packaged database") || message.contains("Migration")) {
             // Handle Schema Mismatch by offering to reset
             tvTitle.text = "Database Error"
             tvMessage.text = "Local database outdated. Resetting..."
             
             // Auto-Reset logic if user encounters this
             // We can just execute reset here or provide button. 
             // Let's provide button to be safe, but make it prominent.
             btnClose.text = "Reset Database"
             btnClose.setOnClickListener {
                 val dbPath = getDatabasePath("library_attendance_db")
                 if (dbPath.exists()) dbPath.delete()
                 val wal = java.io.File(dbPath.path + "-wal")
                 if (wal.exists()) wal.delete()
                 val shm = java.io.File(dbPath.path + "-shm")
                 if (shm.exists()) shm.delete()
                 
                 dialog.dismiss()
                 val intent = baseContext.packageManager.getLaunchIntentForPackage(baseContext.packageName)
                 intent?.addFlags(Intent.FLAG_ACTIVITY_CLEAR_TOP)
                 startActivity(intent)
                 finish()
                 Runtime.getRuntime().exit(0)
             }
             // Do NOT auto dismiss
             dialog.show()
             return 
        } else {
             tvMessage.text = message
             btnClose.setBackgroundColor(ContextCompat.getColor(this, android.R.color.holo_red_dark))
        }
        
        btnClose.setOnClickListener { 
            try { dialog.dismiss() } catch(e: Exception) {} 
        }

        dialog.show()
        
        // Auto dismiss after 3 seconds
        dialogView.postDelayed({ 
            if (dialog.isShowing) {
                try { dialog.dismiss() } catch(e: Exception) {}
            }
        }, 3000)
    }

    private fun setDeviceVolume(percentage: Int) {
        try {
            val audioManager = getSystemService(android.content.Context.AUDIO_SERVICE) as android.media.AudioManager
            val maxVolume = audioManager.getStreamMaxVolume(android.media.AudioManager.STREAM_MUSIC)
            
            // Percentage 0-100
            val targetVolume = (maxVolume * percentage / 100.0).toInt()
            
            audioManager.setStreamVolume(android.media.AudioManager.STREAM_MUSIC, targetVolume, 0)
        } catch (e: Exception) {
            Log.e("Audio", "Cannot set volume", e)
        }
    }

    private fun isGoogleTtsInstalled(context: android.content.Context): Boolean {
        return try {
            context.packageManager.getPackageInfo("com.google.android.tts", 0)
            true
        } catch (e: android.content.pm.PackageManager.NameNotFoundException) {
            false
        }
    }
}
