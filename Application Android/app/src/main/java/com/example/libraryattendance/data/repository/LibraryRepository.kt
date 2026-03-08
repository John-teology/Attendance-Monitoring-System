package com.example.libraryattendance.data.repository

import android.content.Context
import androidx.preference.PreferenceManager
import com.example.libraryattendance.data.local.AttendanceLog
import com.example.libraryattendance.data.local.AttendanceLogDao
import com.example.libraryattendance.data.local.User
import com.example.libraryattendance.data.local.UserDao
import com.example.libraryattendance.data.remote.SyncApiService
import com.example.libraryattendance.data.local.SyncLogDto
import com.example.libraryattendance.data.remote.RawScanDto
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.Flow
import kotlinx.coroutines.withContext
import okhttp3.OkHttpClient
import okhttp3.logging.HttpLoggingInterceptor
import retrofit2.Retrofit
import retrofit2.converter.gson.GsonConverterFactory
import java.util.Calendar
import java.util.concurrent.TimeUnit

import com.example.libraryattendance.data.remote.UserDto

class LibraryRepository(
    private val userDao: UserDao,
    private val logDao: AttendanceLogDao,
    private val context: Context
) {

    val allLogs: Flow<List<AttendanceLog>> = logDao.getAllLogs()

    // Dashboard Stats
    val pendingSyncCount: Flow<Int> = logDao.getPendingSyncCount()
    val syncedCount: Flow<Int> = logDao.getSyncedCount()

    fun getOngoingCount(): Flow<Int> {
        val calendar = Calendar.getInstance().apply {
            set(Calendar.HOUR_OF_DAY, 0)
            set(Calendar.MINUTE, 0)
            set(Calendar.SECOND, 0)
            set(Calendar.MILLISECOND, 0)
        }
        return logDao.getOngoingCount(calendar.timeInMillis)
    }

    fun getTodayCount(): Flow<Int> {
        val calendar = Calendar.getInstance().apply {
            set(Calendar.HOUR_OF_DAY, 0)
            set(Calendar.MINUTE, 0)
            set(Calendar.SECOND, 0)
            set(Calendar.MILLISECOND, 0)
        }
        return logDao.getTodayCount(calendar.timeInMillis)
    }

    suspend fun insertUser(user: User) {
        userDao.insertUser(user)
    }

    suspend fun processScan(data: String, type: String, entryType: String? = null): Result<User> {
        // 1. Verify by ID Number (Priority)
        var user = userDao.getUserByIdNumber(data)
        
        // 2. If not found, try QR Code match
        if (user == null) {
            user = userDao.getUserByQr(data)
        }

        // 3. If not found, try RFID UID match
        if (user == null) {
             user = userDao.getUserByRfid(data)
        }
        
        // 4. Fallback: Try Primary Key ID
        if (user == null && data.toLongOrNull() != null) {
             user = userDao.getUserById(data.toLong())
        }

        return if (user != null) {
            // --- LOCAL ACCESS CONTROL CHECK ---
            if (user.status == "inactive") {
                 return Result.failure(Exception("Access Denied: Account Inactive"))
            }
            
            if (!user.idExpirationDate.isNullOrEmpty()) {
                 try {
                     val dateStr = user.idExpirationDate
                     if (dateStr != null) {
                         val dateFormat = java.text.SimpleDateFormat("yyyy-MM-dd", java.util.Locale.getDefault())
                         val expDate = dateFormat.parse(dateStr)
                         val today = java.util.Date()
                         
                         // Compare only dates (strip time)
                         val expCalendar = Calendar.getInstance().apply { time = expDate!!; set(Calendar.HOUR_OF_DAY, 23); set(Calendar.MINUTE, 59) }
                         val todayCalendar = Calendar.getInstance().apply { time = today }
                         
                         if (todayCalendar.after(expCalendar)) {
                              return Result.failure(Exception("Access Denied: ID Expired on ${user.idExpirationDate}"))
                         }
                     }
                 } catch (e: Exception) {
                     e.printStackTrace()
                 }
            }
            // ---------------------------------

            val log = AttendanceLog(
                userId = user.id,
                scanType = type,
                timestamp = System.currentTimeMillis(),
                entryType = entryType
            )
            logDao.insertLog(log)
            Result.success(user)
        } else {
            Result.failure(Exception("User not found"))
        }
    }

    // Sync Logic
    suspend fun sendRawScan(data: String, type: String): Result<Pair<String, UserDto?>> = withContext(Dispatchers.IO) {
        try {
            val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
            val serverIp = sharedPrefs.getString("server_ip", "192.168.1.100") ?: "192.168.1.100"
            val serverPort = sharedPrefs.getString("server_port", "8000") ?: "8000"
            val baseUrl = "http://$serverIp:$serverPort/"

            // Create OkHttpClient with Header Interceptor
            val client = OkHttpClient.Builder()
                .addInterceptor { chain ->
                    val request = chain.request().newBuilder()
                        .addHeader("X-API-SECRET", "library_secret_key_123")
                        .build()
                    chain.proceed(request)
                }
                .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BODY })
                .connectTimeout(15, TimeUnit.SECONDS)
                .readTimeout(15, TimeUnit.SECONDS)
                .build()

            val apiService = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
                .create(SyncApiService::class.java)

            // Send as generic 'code' to allow backend to check ID Number, RFID, etc.
            // Do NOT send as userId (Primary Key) to avoid PK lookup issues
            val dto = RawScanDto(
                userId = null, 
                code = data,
                timestamp = System.currentTimeMillis(),
                scanType = type
            )

            val response = apiService.sendRawScan(dto)

            if (response.isSuccessful && response.body()?.success == true) {
                // Return entry type (IN/OUT) and User object if available
                val entryType = response.body()?.entry_type ?: "IN"
                val user = response.body()?.user
                Result.success(Pair(entryType, user))
            } else {
                // If it's a known logic error from backend (like "Too fast"), pass that message
                if (response.body()?.success == false && response.body()?.message != null) {
                    Result.failure(Exception(response.body()?.message))
                } else {
                    Result.failure(Exception("Server sync failed: ${response.code()}"))
                }
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun syncUsers(): Result<String> = withContext(Dispatchers.IO) {
        try {
            val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
            val serverIp = sharedPrefs.getString("server_ip", "192.168.1.100") ?: "192.168.1.100"
            val serverPort = sharedPrefs.getString("server_port", "8000") ?: "8000"
            val baseUrl = "http://$serverIp:$serverPort/"

            val client = OkHttpClient.Builder()
                .addInterceptor { chain ->
                    val request = chain.request().newBuilder()
                        .addHeader("X-API-SECRET", "library_secret_key_123")
                        .build()
                    chain.proceed(request)
                }
                .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BODY })
                .connectTimeout(15, TimeUnit.SECONDS)
                .readTimeout(30, TimeUnit.SECONDS) // Longer timeout for large user list
                .build()

            val apiService = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
                .create(SyncApiService::class.java)

            val response = apiService.getUsers()

            if (response.isSuccessful && response.body()?.success == true) {
                val users = response.body()?.users ?: emptyList()
                
                // Insert/Update users
                var count = 0
                users.forEach { dto ->
                    // We now use the Server ID as the Local ID (Primary Key)
                    val user = User(
                        id = dto.id,
                        userType = dto.userType,
                        fullName = dto.fullName,
                        idNumber = dto.idNumber,
                        qrCode = dto.qrCode,
                        rfidUid = dto.rfidUid,
                        status = dto.status,
                        idExpirationDate = dto.idExpirationDate,
                        department = dto.department,
                        course = dto.course,
                        yearLevel = dto.yearLevel,
                        designation = dto.designation,
                        profilePicture = dto.profilePictureUrl // Map DTO (URL) to Entity (String)
                    )
                    
                    userDao.insertUser(user)
                    count++
                }
                
                Result.success("Synced $count users")
            } else {
                Result.failure(Exception("Sync users failed: ${response.code()}"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun syncLogs(): Result<String> = withContext(Dispatchers.IO) {
        try {
            val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
            val serverIp = sharedPrefs.getString("server_ip", "192.168.1.100") ?: "192.168.1.100"
            val serverPort = sharedPrefs.getString("server_port", "8000") ?: "8000"
            val baseUrl = "http://$serverIp:$serverPort/"

            val unsyncedLogs = logDao.getUnsyncedLogsForSync()
            if (unsyncedLogs.isEmpty()) {
                return@withContext Result.success("No logs to sync")
            }

            // Create OkHttpClient with Header Interceptor
            val client = OkHttpClient.Builder()
                .addInterceptor { chain ->
                    val request = chain.request().newBuilder()
                        .addHeader("X-API-SECRET", "library_secret_key_123") // Match .env or middleware
                        .build()
                    chain.proceed(request)
                }
                .addInterceptor(HttpLoggingInterceptor().apply { level = HttpLoggingInterceptor.Level.BODY })
                .connectTimeout(15, TimeUnit.SECONDS)
                .readTimeout(15, TimeUnit.SECONDS)
                .build()

            val apiService = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
                .create(SyncApiService::class.java)

            val response = apiService.syncLogs(unsyncedLogs)

            if (response.isSuccessful && response.body()?.success == true) {
                val syncedIds = response.body()?.syncedIds ?: emptyList()
                // Update local status - we need to refetch original objects to update them or run a query
                // For simplicity, just use the IDs to update. But Room requires objects for update usually, or a query.
                // We'll iterate and update using a custom query if we had one, or just fetch->modify->save
                
                // Fetch full logs to update them
                val allUnsynced = logDao.getUnsyncedLogs()
                allUnsynced.forEach { log ->
                    if (syncedIds.contains(log.id)) {
                         logDao.insertLog(log.copy(syncStatus = true))
                    }
                }
                
                // Also fetch system settings after a successful sync
                syncSystemSettings()

                Result.success("Synced ${syncedIds.size} logs")
            } else {
                Result.failure(Exception("Sync failed: ${response.code()} ${response.message()}"))
            }

        } catch (e: Exception) {
            Result.failure(e)
        }
    }

    suspend fun syncSystemSettings() {
        try {
            val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
            val serverIp = sharedPrefs.getString("server_ip", "192.168.1.100") ?: "192.168.1.100"
            val serverPort = sharedPrefs.getString("server_port", "8000") ?: "8000"
            val baseUrl = "http://$serverIp:$serverPort/"

            val client = OkHttpClient.Builder()
                .addInterceptor { chain ->
                    val request = chain.request().newBuilder()
                        .addHeader("X-API-SECRET", "library_secret_key_123")
                        .build()
                    chain.proceed(request)
                }
                .connectTimeout(5, TimeUnit.SECONDS)
                .build()

            val apiService = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
                .create(SyncApiService::class.java)

            val settingsResponse = apiService.getSystemSettings()
            if (settingsResponse.isSuccessful) {
                val settings = settingsResponse.body()
                settings?.let {
                    // Prepend base URL if logo is a relative path
                    val logoUrl = if (!it.schoolLogoUrl.isNullOrEmpty() && !it.schoolLogoUrl.startsWith("http")) {
                        // Remove leading slash if present to avoid double slashes
                        val cleanPath = if (it.schoolLogoUrl.startsWith("/")) it.schoolLogoUrl.substring(1) else it.schoolLogoUrl
                        "$baseUrl$cleanPath"
                    } else {
                        it.schoolLogoUrl
                    }
                    val bgUrl = if (!it.appBackgroundImage.isNullOrEmpty() && !it.appBackgroundImage.startsWith("http")) {
                        val cleanPath = if (it.appBackgroundImage.startsWith("/")) it.appBackgroundImage.substring(1) else it.appBackgroundImage
                        "$baseUrl$cleanPath"
                    } else {
                        it.appBackgroundImage
                    }
                    saveSystemSettings(it.schoolName, logoUrl, bgUrl, 
                                     it.schoolNameColor, it.buttonBgColor, it.bodyBgColor, it.fontStyle,
                                     it.cardTransparency, it.buttonTransparency, it.iconColor, it.volumeLevel)
                }
            }
        } catch (e: Exception) {
            e.printStackTrace()
        }
    }

    fun saveServerIp(ip: String) {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        sharedPrefs.edit().putString("server_ip", ip).apply()
    }
    
    fun getServerIp(): String {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("server_ip", "192.168.1.100") ?: "192.168.1.100"
    }

    fun saveServerPort(port: String) {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        sharedPrefs.edit().putString("server_port", port).apply()
    }

    fun getServerPort(): String {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("server_port", "8000") ?: "8000"
    }

    // PIN Management
    fun checkPin(inputPin: String): Boolean {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        val storedPin = sharedPrefs.getString("admin_pin", "1234") ?: "1234"
        return inputPin == storedPin
    }

    fun savePin(newPin: String) {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        sharedPrefs.edit().putString("admin_pin", newPin).apply()
    }

    // System Settings
    fun saveSystemSettings(name: String?, logoUrl: String?, bgUrl: String?, 
                          nameColor: String?, btnColor: String?, bodyColor: String?, font: String?,
                          cardTrans: Int?, btnTrans: Int?, iconColor: String?, volumeLevel: Int?) {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        val editor = sharedPrefs.edit()
        
        if (name != null) editor.putString("school_name", name)
        
        if (logoUrl != null) {
            editor.putString("school_logo_url", logoUrl)
        } else {
            editor.remove("school_logo_url")
        }
        
        if (bgUrl != null) {
            editor.putString("app_bg_image", bgUrl)
        } else {
            editor.remove("app_bg_image")
        }

        if (nameColor != null) editor.putString("school_name_color", nameColor)
        if (btnColor != null) editor.putString("button_bg_color", btnColor)
        if (bodyColor != null) editor.putString("body_bg_color", bodyColor)
        if (font != null) editor.putString("font_style", font)
        
        if (cardTrans != null) editor.putInt("card_transparency", cardTrans)
        if (btnTrans != null) editor.putInt("button_transparency", btnTrans)
        
        if (iconColor != null) editor.putString("icon_color", iconColor)

        if (volumeLevel != null) {
            editor.putInt("volume_level", volumeLevel)
        } else {
            editor.remove("volume_level")
        }
        
        editor.apply()
    }

    fun getSavedSchoolName(): String {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("school_name", "Library Attendance") ?: "Library Attendance"
    }

    fun getSavedSchoolLogoUrl(): String? {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("school_logo_url", null)
    }

    fun getSavedAppBackgroundImage(): String? {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("app_bg_image", null)
    }

    fun getSavedSchoolNameColor(): String? {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("school_name_color", null)
    }

    fun getSavedButtonBgColor(): String? {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("button_bg_color", null)
    }

    fun getSavedBodyBgColor(): String? {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("body_bg_color", null)
    }

    fun getSavedFontStyle(): String? {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("font_style", null)
    }

    fun getSavedCardTransparency(): Int {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getInt("card_transparency", 80) // Default 80%
    }

    fun getSavedButtonTransparency(): Int {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getInt("button_transparency", 100) // Default 100%
    }
    
    fun getSavedIconColor(): String? {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        return sharedPrefs.getString("icon_color", null)
    }

    fun getSavedVolumeLevel(): Int? {
        val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
        if (sharedPrefs.contains("volume_level")) {
            return sharedPrefs.getInt("volume_level", 80)
        }
        return null
    }

    suspend fun checkServerAvailability(): Boolean = withContext(Dispatchers.IO) {
        try {
            val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
            val serverIp = sharedPrefs.getString("server_ip", "192.168.1.100") ?: "192.168.1.100"
            val serverPort = sharedPrefs.getString("server_port", "8000") ?: "8000"
            val baseUrl = "http://$serverIp:$serverPort/"

            val client = OkHttpClient.Builder()
                .connectTimeout(2, TimeUnit.SECONDS) // Short timeout for health check
                .readTimeout(2, TimeUnit.SECONDS)
                .build()

            val request = okhttp3.Request.Builder()
                .url(baseUrl + "api/settings") // Lightweight endpoint to check connectivity
                .head() // HEAD request is lighter than GET
                .build()

            val response = client.newCall(request).execute()
            response.isSuccessful
        } catch (e: Exception) {
            false
        }
    }

    suspend fun fetchStats(): Result<com.example.libraryattendance.data.remote.StatsResponse> = withContext(Dispatchers.IO) {
        try {
            val sharedPrefs = PreferenceManager.getDefaultSharedPreferences(context)
            val serverIp = sharedPrefs.getString("server_ip", "192.168.1.100") ?: "192.168.1.100"
            val serverPort = sharedPrefs.getString("server_port", "8000") ?: "8000"
            val baseUrl = "http://$serverIp:$serverPort/"

            val client = OkHttpClient.Builder()
                .addInterceptor { chain ->
                    val request = chain.request().newBuilder()
                        .addHeader("X-API-SECRET", "library_secret_key_123")
                        .build()
                    chain.proceed(request)
                }
                .connectTimeout(5, TimeUnit.SECONDS)
                .build()

            val apiService = Retrofit.Builder()
                .baseUrl(baseUrl)
                .client(client)
                .addConverterFactory(GsonConverterFactory.create())
                .build()
                .create(SyncApiService::class.java)

            val response = apiService.getStats()
            if (response.isSuccessful && response.body()?.success == true) {
                Result.success(response.body()!!)
            } else {
                Result.failure(Exception("Failed to fetch stats"))
            }
        } catch (e: Exception) {
            Result.failure(e)
        }
    }
}
