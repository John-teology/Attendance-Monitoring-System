package com.example.libraryattendance.data.remote

import com.example.libraryattendance.data.local.SyncLogDto
import retrofit2.Response
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import com.google.gson.annotations.SerializedName

interface SyncApiService {
    @POST("api/attendance/sync")
    suspend fun syncLogs(@Body logs: List<SyncLogDto>): Response<SyncResponse>

    @POST("api/attendance/sync")
    suspend fun sendRawScan(@Body log: RawScanDto): Response<SyncResponse>

    @GET("api/system-settings")
    suspend fun getSystemSettings(): Response<SystemSettingsDto>

    @GET("api/users")
    suspend fun getUsers(): Response<UserListResponse>

    @GET("api/attendance/stats")
    suspend fun getStats(): Response<StatsResponse>
}

data class StatsResponse(
    val success: Boolean,
    @SerializedName("active_count") val activeCount: Int,
    @SerializedName("ongoing_count") val ongoingCount: Int
)
