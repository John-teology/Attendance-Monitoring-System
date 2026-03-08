package com.example.libraryattendance.ui

import androidx.lifecycle.LiveData
import androidx.lifecycle.MutableLiveData
import androidx.lifecycle.ViewModel
import androidx.lifecycle.ViewModelProvider
import androidx.lifecycle.asLiveData
import androidx.lifecycle.viewModelScope
import com.example.libraryattendance.data.local.AttendanceLog
import com.example.libraryattendance.data.local.User
import com.example.libraryattendance.data.repository.LibraryRepository
import kotlinx.coroutines.launch

class MainViewModel(private val repository: LibraryRepository) : ViewModel() {

    val allLogs: LiveData<List<AttendanceLog>> = repository.allLogs.asLiveData()
    
    // Dashboard Stats
    private val _todayCount = MutableLiveData<Int>(0)
    val todayCount: LiveData<Int> = _todayCount

    private val _ongoingCount = MutableLiveData<Int>(0)
    val ongoingCount: LiveData<Int> = _ongoingCount
    
    val pendingSyncCount: LiveData<Int> = repository.pendingSyncCount.asLiveData()
    // Removed local ongoingCount observation to prefer server stats

    private val _scanResult = MutableLiveData<ScanResult>()
    val scanResult: LiveData<ScanResult> = _scanResult

    private val _syncResult = MutableLiveData<String>()
    val syncResult: LiveData<String> = _syncResult

    private val _schoolName = MutableLiveData<String>()
    val schoolName: LiveData<String> = _schoolName

    private val _schoolLogoUrl = MutableLiveData<String?>()
    val schoolLogoUrl: LiveData<String?> = _schoolLogoUrl

    private val _appBackgroundImage = MutableLiveData<String?>()
    val appBackgroundImage: LiveData<String?> = _appBackgroundImage

    private val _schoolNameColor = MutableLiveData<String?>()
    val schoolNameColor: LiveData<String?> = _schoolNameColor

    private val _buttonBgColor = MutableLiveData<String?>()
    val buttonBgColor: LiveData<String?> = _buttonBgColor

    private val _bodyBgColor = MutableLiveData<String?>()
    val bodyBgColor: LiveData<String?> = _bodyBgColor

    private val _fontStyle = MutableLiveData<String?>()
    val fontStyle: LiveData<String?> = _fontStyle

    private val _cardTransparency = MutableLiveData<Int>()
    val cardTransparency: LiveData<Int> = _cardTransparency

    private val _buttonTransparency = MutableLiveData<Int>()
    val buttonTransparency: LiveData<Int> = _buttonTransparency

    private val _iconColor = MutableLiveData<String?>()
    val iconColor: LiveData<String?> = _iconColor

    private val _volumeLevel = MutableLiveData<Int?>()
    val volumeLevel: LiveData<Int?> = _volumeLevel

    init {
        // Load initial settings
        _schoolName.value = repository.getSavedSchoolName()
        _schoolLogoUrl.value = repository.getSavedSchoolLogoUrl()
        _appBackgroundImage.value = repository.getSavedAppBackgroundImage()
        _schoolNameColor.value = repository.getSavedSchoolNameColor()
        _buttonBgColor.value = repository.getSavedButtonBgColor()
        _bodyBgColor.value = repository.getSavedBodyBgColor()
        _fontStyle.value = repository.getSavedFontStyle()
        _cardTransparency.value = repository.getSavedCardTransparency()
        _buttonTransparency.value = repository.getSavedButtonTransparency()
        _iconColor.value = repository.getSavedIconColor()
        _volumeLevel.value = repository.getSavedVolumeLevel()
        
        // Auto-sync users and logs on startup
        syncUsers()
        syncLogs()
        refreshStats()
        
        // Force sync settings independently
        viewModelScope.launch {
            repository.syncSystemSettings()
            _schoolName.value = repository.getSavedSchoolName()
            _schoolLogoUrl.value = repository.getSavedSchoolLogoUrl()
            _appBackgroundImage.value = repository.getSavedAppBackgroundImage()
            _schoolNameColor.value = repository.getSavedSchoolNameColor()
            _buttonBgColor.value = repository.getSavedButtonBgColor()
            _bodyBgColor.value = repository.getSavedBodyBgColor()
            _fontStyle.value = repository.getSavedFontStyle()
            _cardTransparency.value = repository.getSavedCardTransparency()
            _buttonTransparency.value = repository.getSavedButtonTransparency()
            _iconColor.value = repository.getSavedIconColor()
            _volumeLevel.value = repository.getSavedVolumeLevel()
        }
    }
    
    fun refreshStats() {
        viewModelScope.launch {
            val result = repository.fetchStats()
            if (result.isSuccess) {
                val stats = result.getOrNull()
                stats?.let {
                    _todayCount.value = it.activeCount
                    _ongoingCount.value = it.ongoingCount
                }
            }
        }
    }
    
    fun syncUsers() {
        viewModelScope.launch {
            val result = repository.syncUsers()
            if (result.isSuccess) {
                // Optionally log or show small toast
            }
        }
    }

    fun processScan(data: String, type: String) {
        viewModelScope.launch {
            // ALWAYS try to send to server first/immediately
            val serverResult = repository.sendRawScan(data, type)
            val serverException = serverResult.exceptionOrNull()
            
            // 1. Server explicitly denied it (User not found, Expired, Too fast)
            if (serverResult.isFailure) {
                val errorMsg = serverException?.message
                if (errorMsg != null && (errorMsg.contains("Access Denied") || errorMsg.contains("Too fast"))) {
                     _scanResult.value = ScanResult.Error(errorMsg)
                     
                     // We are online (server responded), so sync logs and stats
                     syncLogs()
                     refreshStats()
                     
                     return@launch
                }
            }

            // Determine entry type from server if available
            val resultPair = serverResult.getOrNull()
            val entryType = resultPair?.first
            val serverUserDto = resultPair?.second

            // 2. Server Success - Use Server Data Authority
            if (serverResult.isSuccess && serverUserDto != null) {
                 val user = User(
                     id = serverUserDto.id,
                     userType = serverUserDto.userType,
                     fullName = serverUserDto.fullName,
                     idNumber = serverUserDto.idNumber,
                     qrCode = serverUserDto.qrCode,
                     rfidUid = serverUserDto.rfidUid,
                     status = serverUserDto.status,
                     idExpirationDate = serverUserDto.idExpirationDate,
                     department = serverUserDto.department,
                     course = serverUserDto.course,
                     yearLevel = serverUserDto.yearLevel,
                     designation = serverUserDto.designation,
                     profilePicture = serverUserDto.profilePictureUrl
                 )
                 // Update local cache to ensure consistency
                 addUser(user)
                 
                 _scanResult.value = ScanResult.Success(user, entryType)
                 
                 // Trigger sync to update status flags if needed
                 syncLogs()
                 refreshStats()
                 return@launch
            }

            // 3. Offline / Server Unreachable
            // User requested: "Offline your action wont be recorded"
            // We do NOT fall back to local database anymore.
            
            // However, per request: "every scan it still check if it online and try to sync"
            // If we reached here, sendRawScan failed (likely network), but we try one more check
            // or if it failed due to 500 error but server is up.
            val isOnline = repository.checkServerAvailability()
            if (isOnline) {
                syncLogs()
                refreshStats()
            }
            
            _scanResult.value = ScanResult.Error("System Offline: Your action won't be recorded.")
        }
    }

    fun addUser(user: User) {
        viewModelScope.launch {
            repository.insertUser(user)
        }
    }
    
    fun syncLogs() {
        viewModelScope.launch {
            _syncResult.value = "Syncing..."
            val result = repository.syncLogs()
            
            // Always sync settings when syncing logs
            repository.syncSystemSettings()
            
            if (result.isSuccess) {
                _syncResult.value = result.getOrNull() ?: "Sync complete"
                // Refresh settings
                _schoolName.value = repository.getSavedSchoolName()
                _schoolLogoUrl.value = repository.getSavedSchoolLogoUrl()
                _appBackgroundImage.value = repository.getSavedAppBackgroundImage()
                _schoolNameColor.value = repository.getSavedSchoolNameColor()
                _buttonBgColor.value = repository.getSavedButtonBgColor()
                _bodyBgColor.value = repository.getSavedBodyBgColor()
                _fontStyle.value = repository.getSavedFontStyle()
                _cardTransparency.value = repository.getSavedCardTransparency()
                _buttonTransparency.value = repository.getSavedButtonTransparency()
                _iconColor.value = repository.getSavedIconColor()
                _volumeLevel.value = repository.getSavedVolumeLevel()
                
                // Refresh stats after successful sync
                refreshStats()
            } else {
                _syncResult.value = "Sync failed: ${result.exceptionOrNull()?.message}"
            }
        }
    }

    fun saveServerIp(ip: String) {
        repository.saveServerIp(ip)
    }

    fun getServerIp(): String {
        return repository.getServerIp()
    }

    fun saveServerPort(port: String) {
        repository.saveServerPort(port)
    }

    fun getServerPort(): String {
        return repository.getServerPort()
    }
    
    fun checkPin(pin: String): Boolean {
        return repository.checkPin(pin)
    }
    
    fun savePin(pin: String) {
        repository.savePin(pin)
    }
}

sealed class ScanResult {
    data class Success(val user: User, val entryType: String? = null, val isOffline: Boolean = false) : ScanResult()
    data class Error(val message: String) : ScanResult()
}

class MainViewModelFactory(private val repository: LibraryRepository) : ViewModelProvider.Factory {
    override fun <T : ViewModel> create(modelClass: Class<T>): T {
        if (modelClass.isAssignableFrom(MainViewModel::class.java)) {
            @Suppress("UNCHECKED_CAST")
            return MainViewModel(repository) as T
        }
        throw IllegalArgumentException("Unknown ViewModel class")
    }
}
